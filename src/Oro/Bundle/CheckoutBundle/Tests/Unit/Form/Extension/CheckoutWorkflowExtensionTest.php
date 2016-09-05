<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;
use Oro\Bundle\CheckoutBundle\Form\Extension\CheckoutWorkflowExtension;

class CheckoutWorkflowExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FlashBag
     */
    protected $flashBag;

    /**
     * @var CheckoutWorkflowExtension
     */
    protected $checkoutWorkflowExtension;

    protected function setUp()
    {
        $this->flashBag = new FlashBag();
        $this->checkoutWorkflowExtension = new CheckoutWorkflowExtension($this->flashBag);
    }

    protected function tearDown()
    {
        unset($this->flashBag, $this->checkoutWorkflowExtension);
    }

    /**
     * @param array $passedFormErrors
     * @param array $expectedFormErrors
     * @param array $expectedFlashMessages
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $passedFormErrors, array $expectedFormErrors, array $expectedFlashMessages)
    {
        $form = $this->createForm('main');
        $view = new FormView();
        $view->vars['errors'] = new FormErrorIterator($form, $passedFormErrors);
        $expectedErrors = new FormErrorIterator($form, $expectedFormErrors);

        $this->checkoutWorkflowExtension->finishView($view, $form, []);

        $this->assertEquals((string)$view->vars['errors'], (string)$expectedErrors);

        $actualMessages = $this->flashBag->peekAll();
        $this->assertEquals($expectedFlashMessages, $actualMessages);
    }

    /**
     * @return array
     */
    public function finishViewProvider()
    {
        $error1 = new FormError('error1');
        $orderChangedError = new FormError('oro.checkout.workflow.condition.content_of_order_was_changed.message');
        $nestedForm = $this->createForm('nested');

        return [
            'some errors not related to workflow state' => [
                'passedFormErrors' => [
                    $error1,
                ],
                'expectedFormErrors' => [
                    $error1,
                ],
                'expectedFlashMessages' => [],
            ],
            'workflow related error' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error1,
                ],
                'expectedFormErrors' => [
                    $error1,
                ],
                'expectedMessages' => [
                    'warning' => ['oro.checkout.workflow.condition.content_of_order_was_changed.message'],
                ],
            ],
            'nested errors' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error1,
                    new FormErrorIterator($nestedForm, [
                        $orderChangedError,
                        $error1,
                    ]),
                ],
                'expectedFormErrors' => [
                    $error1,
                    new FormErrorIterator($nestedForm, [
                        $error1,
                    ]),
                ],
                'expectedMessages' => [
                    'warning' => ['oro.checkout.workflow.condition.content_of_order_was_changed.message'],
                ],
            ],
        ];
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(WorkflowTransitionType::class, $this->checkoutWorkflowExtension->getExtendedType());
    }

    /**
     * @param string $name
     * @return \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected function createForm($name)
    {
        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form * */
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $form;
    }
}
