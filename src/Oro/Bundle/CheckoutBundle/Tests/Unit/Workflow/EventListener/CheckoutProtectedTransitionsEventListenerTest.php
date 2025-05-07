<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Workflow\EventListener\CheckoutProtectedTransitionsEventListener;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceInterface;
use Oro\Bundle\FormBundle\Captcha\CaptchaServiceRegistry;
use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProvider;
use Oro\Bundle\FormBundle\Form\Type\CaptchaType;
use Oro\Bundle\FormBundle\Form\Type\ReCaptchaType;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreGuardEvent;
use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionAssembleEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutProtectedTransitionsEventListenerTest extends TestCase
{
    private CaptchaSettingsProvider&MockObject $captchaSettingsProvider;
    private CaptchaServiceRegistry&MockObject $captchaServiceRegistry;
    private TranslatorInterface&MockObject $translator;
    private CheckoutProtectedTransitionsEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->captchaSettingsProvider = $this->createMock(CaptchaSettingsProvider::class);
        $this->captchaServiceRegistry = $this->createMock(CaptchaServiceRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->listener = new CheckoutProtectedTransitionsEventListener(
            $this->captchaSettingsProvider,
            $this->captchaServiceRegistry,
            $this->translator
        );
    }

    public function testOnPreGuardWhenAlreadyDenied(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->never())
            ->method('getFrontendOptions');
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreGuardEvent($workflowItem, $transition, false);

        $this->captchaSettingsProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->onPreGuard($event);

        $this->assertFalse($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaProtectionNotEnabledForTransition(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn([]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->never())
            ->method($this->anything());

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaProtectionNotAvailable(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_captcha_protected' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(false);

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaProtectionNotEnabledForForm(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_captcha_protected' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(false);

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaAlreadyChecked(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_captcha_protected' => true]);
        $result = new WorkflowResult(['is_captcha_checked' => true]);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(true);

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaProtectionIsEnabled(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_captcha_protected' => true]);
        $result = new WorkflowResult();
        $data = new WorkflowData(['captcha' => 'valid']);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(true);

        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('valid')
            ->willReturn(true);

        $this->listener->onPreGuard($event);

        $this->assertTrue($event->isAllowed());
    }

    public function testOnPreGuardWhenCaptchaTokenNotVerified(): void
    {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->once())
            ->method('getFrontendOptions')
            ->willReturn(['is_captcha_protected' => true]);
        $result = new WorkflowResult();
        $data = new WorkflowData(['captcha' => 'invalid']);
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($result);
        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($data);
        $event = new PreGuardEvent($workflowItem, $transition, true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(true);

        $captchaService = $this->createMock(CaptchaServiceInterface::class);
        $this->captchaServiceRegistry->expects($this->once())
            ->method('getCaptchaService')
            ->willReturn($captchaService);

        $captchaService->expects($this->once())
            ->method('isVerified')
            ->with('invalid')
            ->willReturn(false);

        $this->listener->onPreGuard($event);

        $this->assertFalse($event->isAllowed());
    }

    public function testOnAssembleWhenProtectionNotAvailable(): void
    {
        $event = new TransitionAssembleEvent('test_transition', [], [], [], []);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(false);

        $this->listener->onAssemble($event);

        $this->assertSame([], $event->getOptions());
    }

    public function testOnAssembleWhenNotProtectedForm(): void
    {
        $event = new TransitionAssembleEvent('test_transition', [], [], [], []);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(false);

        $this->listener->onAssemble($event);

        $this->assertSame([], $event->getOptions());
    }

    public function testOnAssembleAddsCaptchaWithDefaultWarning(): void
    {
        $options = ['frontend_options' => ['is_captcha_protected' => true]];
        $event = new TransitionAssembleEvent('test_transition', $options, [], [], []);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('getFormType')
            ->willReturn(CaptchaType::class);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('translated_title');

        $this->listener->onAssemble($event);

        $options = $event->getOptions();
        $this->assertArrayHasKey('form_options', $options);
        $this->assertArrayHasKey('attribute_fields', $options['form_options']);
        $this->assertArrayHasKey('captcha', $options['form_options']['attribute_fields']);
        $this->assertEquals('translated_title', $options['frontend_options']['data']['dialog-title']);
        $this->assertSame('oro.workflow.checkout.captcha.warning.message', $options['message']);
    }

    public function testOnAssembleAddsCaptchaWithRecaptchaWarning(): void
    {
        $options = ['frontend_options' => ['is_captcha_protected' => true]];
        $event = new TransitionAssembleEvent('test_transition', $options, [], [], []);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with('oro_workflow_checkout_start')
            ->willReturn(true);

        $this->captchaSettingsProvider->expects($this->once())
            ->method('getFormType')
            ->willReturn(ReCaptchaType::class);

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn('translated_title');

        $this->listener->onAssemble($event);

        $options = $event->getOptions();
        $this->assertSame('oro.workflow.checkout.captcha.recaptcha.warning.message', $options['message']);
    }
}
