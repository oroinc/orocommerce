<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Handler;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;

class CheckoutErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FlashBag */
    protected $flashBag;

    /** @var CheckoutErrorHandler */
    protected $handler;

    protected function setUp()
    {
        $this->flashBag = new FlashBag();
        $this->handler = new CheckoutErrorHandler($this->flashBag);
    }

    protected function tearDown()
    {
        unset($this->handler, $this->flashBag);
    }

    /**
     * @dataProvider filterWorkflowStateErrorProvider
     * @param array $passedFormErrors
     * @param array $expectedFormErrors
     */
    public function testFilterWorkflowStateError(array $passedFormErrors, array $expectedFormErrors)
    {
        $form = $this->createForm('main');
        $errorIterator = new FormErrorIterator($form, $passedFormErrors);
        $expectedErrorIterator = new FormErrorIterator($form, $expectedFormErrors);

        $this->assertEquals($expectedErrorIterator, $this->handler->filterWorkflowStateError($errorIterator));
    }

    /**
     * @return array
     */
    public function filterWorkflowStateErrorProvider()
    {
        $error = new FormError('error');
        $orderChangedError = new FormError('oro.checkout.workflow.condition.content_of_order_was_changed.message');
        $nestedForm = $this->createForm('nested');

        return [
            'some errors not related to workflow state' => [
                'passedFormErrors' => [
                    $error,
                ],
                'expectedFormErrors' => [
                    $error,
                ],
            ],
            'workflow related error' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error,
                ],
                'expectedFormErrors' => [
                    $error,
                ],
            ],
            'nested errors' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error,
                    new FormErrorIterator($nestedForm, [
                        $orderChangedError,
                        $error,
                    ]),
                ],
                'expectedFormErrors' => [
                    $error,
                    new FormErrorIterator($nestedForm, [
                        $error,
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider addFlashWorkflowStateWarningProvider
     * @param array $passedFormErrors
     * @param array $expectedFlashMessages
     */
    public function testAddFlashWorkflowStateWarning(array $passedFormErrors, array $expectedFlashMessages)
    {
        $form = $this->createForm('main');
        $errorIterator = new FormErrorIterator($form, $passedFormErrors);
        $this->handler->addFlashWorkflowStateWarning($errorIterator);

        $actualFlashMessages = $this->flashBag->peekAll();
        $this->assertEquals($expectedFlashMessages, $actualFlashMessages);
    }

    /**
     * @return array
     */
    public function addFlashWorkflowStateWarningProvider()
    {
        $error = new FormError('error');
        $orderChangedError = new FormError('oro.checkout.workflow.condition.content_of_order_was_changed.message');
        $nestedForm = $this->createForm('nested');

        return [
            'some errors not related to workflow state' => [
                'passedFormErrors' => [
                    $error,
                ],
                'expectedFlashMessages' => [],
            ],
            'workflow related error' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error,
                ],
                'expectedMessages' => [
                    'warning' => ['oro.checkout.workflow.condition.content_of_order_was_changed.message'],
                ],
            ],
            'nested errors' => [
                'passedFormErrors' => [
                    $orderChangedError,
                    $error,
                    new FormErrorIterator($nestedForm, [
                        $orderChangedError,
                        $error,
                    ]),
                ],
                'expectedMessages' => [
                    'warning' => ['oro.checkout.workflow.condition.content_of_order_was_changed.message'],
                ],
            ],
        ];
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
