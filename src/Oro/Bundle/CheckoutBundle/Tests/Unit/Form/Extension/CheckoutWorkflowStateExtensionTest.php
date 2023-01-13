<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowStateExtension;
use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class CheckoutWorkflowStateExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutErrorHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutErrorHandler;

    /** @var CheckoutWorkflowStateExtension */
    private $checkoutWorkflowExtension;

    protected function setUp(): void
    {
        $this->checkoutErrorHandler = $this->createMock(CheckoutErrorHandler::class);

        $this->checkoutWorkflowExtension = new CheckoutWorkflowStateExtension($this->checkoutErrorHandler);
    }

    public function testFinishView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $view->vars['errors'] = new FormErrorIterator($form, [new FormError('')]);
        $expectedErrors = new FormErrorIterator($form, []);

        $this->checkoutErrorHandler->expects($this->once())
            ->method('filterWorkflowStateError')
            ->with($view->vars['errors'])
            ->willReturn($expectedErrors);

        $this->checkoutWorkflowExtension->finishView($view, $form, []);

        $this->assertSame($expectedErrors, $view->vars['errors']);
    }

    public function testFinishViewWithEmptyErrors()
    {
        $form = $this->createMock(FormInterface::class);

        $this->checkoutErrorHandler->expects($this->once())
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
        $this->assertEquals($expectedErrors, $view->vars['errors']);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([WorkflowTransitionType::class], CheckoutWorkflowStateExtension::getExtendedTypes());
    }
}
