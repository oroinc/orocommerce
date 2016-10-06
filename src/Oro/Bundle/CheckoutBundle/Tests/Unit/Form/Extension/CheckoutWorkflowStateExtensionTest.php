<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowStateExtension;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class CheckoutWorkflowStateExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutWorkflowStateExtension
     */
    protected $checkoutWorkflowExtension;

    /** @var CheckoutErrorHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $checkoutErrorHandler;

    protected function setUp()
    {
        $this->checkoutErrorHandler = $this->getMockBuilder(CheckoutErrorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutWorkflowExtension = new CheckoutWorkflowStateExtension($this->checkoutErrorHandler);
    }

    protected function tearDown()
    {
        unset($this->checkoutErrorHandler, $this->checkoutWorkflowExtension);
    }

    public function testFinishView()
    {
        $form = $this->createForm('main');
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
        $form = $this->createForm('main');

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

    public function testGetExtendedType()
    {
        $this->assertEquals(WorkflowTransitionType::class, $this->checkoutWorkflowExtension->getExtendedType());
    }

    /**
     * @param string $name
     * @return FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createForm($name)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $form;
    }
}
