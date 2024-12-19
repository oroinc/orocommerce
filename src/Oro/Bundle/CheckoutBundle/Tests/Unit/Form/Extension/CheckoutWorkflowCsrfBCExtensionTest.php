<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowCsrfBCExtension;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CheckoutWorkflowCsrfBCExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const CSRF_FIELD_NAME = 'token_field';

    /** @var CheckoutWorkflowCsrfBCExtension */
    private $checkoutWorkflowCsrfBCExtension;

    protected function setUp(): void
    {
        $this->checkoutWorkflowCsrfBCExtension = new CheckoutWorkflowCsrfBCExtension(self::CSRF_FIELD_NAME);
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([WorkflowTransitionType::class], CheckoutWorkflowCsrfBCExtension::getExtendedTypes());
    }

    public function testFinishView(): void
    {
        $config = $this->assertFormConfig();
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $options = [];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayHasKey(self::CSRF_FIELD_NAME, $formView->children);
    }

    public function testFinishViewWithEnabledCsrf(): void
    {
        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = ['csrf_protection' => true];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayNotHasKey(self::CSRF_FIELD_NAME, $formView->children);
    }

    public function testFinishViewWithCsrfField(): void
    {
        $formView = new FormView();
        $formView->children[self::CSRF_FIELD_NAME] = true;
        $form = $this->createMock(FormInterface::class);
        $options = ['csrf_protection' => false];

        $this->checkoutWorkflowCsrfBCExtension->finishView($formView, $form, $options);

        $this->assertArrayHasKey(self::CSRF_FIELD_NAME, $formView->children);
        $this->assertTrue($formView->children[self::CSRF_FIELD_NAME]);
    }

    private function assertFormConfig(): FormConfigInterface|MockObject
    {
        $csrfForm = $this->createMock(FormInterface::class);
        $csrfForm
            ->expects($this->once())
            ->method('createView')
            ->willReturn(new FormView());

        $factory = $this->createMock(FormFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('createNamed')
            ->willReturn($csrfForm);

        $config = $this->createMock(FormConfigInterface::class);
        $config
            ->expects($this->once())
            ->method('getFormFactory')
            ->willReturn($factory);

        return $config;
    }
}
