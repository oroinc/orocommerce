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
    /**
     * @var CheckoutWorkflowStateExtension
     */
    protected $checkoutWorkflowExtension;

    /** @var CheckoutErrorHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $checkoutErrorHandler;

    protected function setUp(): void
    {
        $this->checkoutErrorHandler = $this->getMockBuilder(CheckoutErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutWorkflowExtension = new CheckoutWorkflowStateExtension($this->checkoutErrorHandler);
    }

    protected function tearDown(): void
    {
        unset($this->checkoutErrorHandler, $this->checkoutWorkflowExtension);
    }

    public function testFinishView()
    {
        $form = $this->createForm();
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
        $form = $this->createForm();

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

    /**
     * @return FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createForm()
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $form;
    }
}
