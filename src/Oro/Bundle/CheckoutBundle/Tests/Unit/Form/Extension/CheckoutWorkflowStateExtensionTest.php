<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowStateExtension;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Layout\Extension\Theme\Model\CurrentThemeProvider;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class CheckoutWorkflowStateExtensionTest extends TestCase
{
    private CheckoutErrorHandler&MockObject $checkoutErrorHandler;
    private CurrentThemeProvider&MockObject $currentThemeProvider;
    private ThemeManager&MockObject $themeManager;

    private CheckoutWorkflowStateExtension $checkoutWorkflowExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutErrorHandler = $this->createMock(CheckoutErrorHandler::class);
        $this->currentThemeProvider = $this->createMock(CurrentThemeProvider::class);
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->checkoutWorkflowExtension = new CheckoutWorkflowStateExtension(
            $this->checkoutErrorHandler,
            $this->currentThemeProvider,
            $this->themeManager
        );
    }

    public function testPreSubmitIsOldTheme(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(true);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::never())
            ->method('getData');

        $this->checkoutWorkflowExtension->onPreSubmit(new FormEvent($form, []));
    }

    public function testPreSubmitNoWorkflowData(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn([]);

        $form->expects(self::never())
            ->method('getConfig');

        $this->checkoutWorkflowExtension->onPreSubmit(new FormEvent($form, []));
    }

    public function testPreSubmitNoMatchedTransition(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn(new WorkflowData());

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::once())
            ->method('getOption')
            ->with('transition_name')
            ->willReturn('test');

        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $form->expects(self::never())
            ->method('all');

        $this->checkoutWorkflowExtension->onPreSubmit(new FormEvent($form, []));
    }

    public function testPreSubmitNoEnterManuallyAddress(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn(new WorkflowData());

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::once())
            ->method('getOption')
            ->with('transition_name')
            ->willReturn('continue_to_shipping_address');

        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $form->expects(self::never())
            ->method('all');

        $this->checkoutWorkflowExtension->onPreSubmit(new FormEvent($form, []));
    }

    public function testPreSubmit(): void
    {
        $this->currentThemeProvider->expects(self::once())
            ->method('getCurrentThemeId')
            ->willReturn('default');

        $this->themeManager->expects(self::once())
            ->method('themeHasParent')
            ->with('default', ['default_50', 'default_51'])
            ->willReturn(false);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())
            ->method('getData')
            ->willReturn(new WorkflowData());

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects(self::once())
            ->method('getOption')
            ->with('transition_name')
            ->willReturn('continue_to_shipping_address');

        $form->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        $child = $this->createMock(FormInterface::class);
        $child->expects(self::exactly(2))
            ->method('getName')
            ->willReturn('email');

        $child2 = $this->createMock(FormInterface::class);
        $child2->expects(self::once())
            ->method('getName')
            ->willReturn('test');

        $form->expects(self::once())
            ->method('all')
            ->willReturn([$child, $child2]);

        $this->checkoutWorkflowExtension->onPreSubmit(
            new FormEvent($form, ['billing_address' => ['customerAddress' => '0']])
        );
    }

    public function testFinishView(): void
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $view->vars['errors'] = new FormErrorIterator($form, [new FormError('')]);
        $expectedErrors = new FormErrorIterator($form, []);

        $this->checkoutErrorHandler->expects(self::once())
            ->method('filterWorkflowStateError')
            ->with($view->vars['errors'])
            ->willReturn($expectedErrors);

        $this->checkoutWorkflowExtension->finishView($view, $form, []);

        self::assertSame($expectedErrors, $view->vars['errors']);
    }

    public function testFinishViewWithEmptyErrors(): void
    {
        $form = $this->createMock(FormInterface::class);

        $this->checkoutErrorHandler->expects(self::once())
            ->method('filterWorkflowStateError')
            ->with($this->isInstanceOf(FormErrorIterator::class))
            ->willReturnCallback(function (FormErrorIterator $errors) use ($form) {
                $this->assertEquals($form, $errors->getForm());
                $this->assertEquals(0, $errors->count());

                return $errors;
            });

        $view = new FormView();
        $this->checkoutWorkflowExtension->finishView($view, $form, []);

        $expectedErrors = new FormErrorIterator($form, []);
        self::assertEquals($expectedErrors, $view->vars['errors']);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([WorkflowTransitionType::class], CheckoutWorkflowStateExtension::getExtendedTypes());
    }
}
