<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Handler;

use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\Validator\ConstraintViolation;

class CheckoutErrorHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FlashBag */
    protected $flashBag;

    /** @var CheckoutErrorHandler */
    protected $handler;

    protected function setUp(): void
    {
        $this->flashBag = new FlashBag();
        $this->handler = new CheckoutErrorHandler($this->flashBag);
    }

    protected function tearDown(): void
    {
        unset($this->handler, $this->flashBag);
    }

    /**
     * @dataProvider filterWorkflowStateErrorProvider
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
     * @return FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createForm($name)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->getMockBuilder(FormInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $form;
    }

    /**
     * @dataProvider getWorkflowErrorsDataTransformer
     */
    public function testGetWorkflowErrors(FormErrorIterator $errorIterator, array $expectedErrors): void
    {
        $this->assertEquals($expectedErrors, $this->handler->getWorkflowErrors($errorIterator));
    }

    public function getWorkflowErrorsDataTransformer(): array
    {
        $transitionIsAllowedConstraintViolation = $this->createMock(ConstraintViolation::class);
        $transitionIsAllowedConstraintViolation
            ->expects($this->any())
            ->method('getConstraint')
            ->willReturn($this->createMock(TransitionIsAllowed::class));

        return [
            'no errors' => [
                'errorIterator' => new FormErrorIterator($this->createMock(FormInterface::class), []),
                'expectedErrors' => [],
            ],
            'no ConstraintViolation errors' => [
                'errorIterator' => new FormErrorIterator(
                    $this->createMock(FormInterface::class),
                    [new FormError('sample_error')]
                ),
                'expectedErrors' => [],
            ],
            'no TransitionIsAllowed errors' => [
                'errorIterator' => new FormErrorIterator(
                    $this->createMock(FormInterface::class),
                    [new FormError('sample_error', null, [], null, $this->createMock(ConstraintViolation::class))]
                ),
                'expectedErrors' => [],
            ],
            'nested FormErrorIterator errors' => [
                'errorIterator' => new FormErrorIterator(
                    $this->createMock(FormInterface::class),
                    [
                        new FormErrorIterator(
                            $this->createMock(FormInterface::class),
                            [
                                new FormError(
                                    'sample_error',
                                    null,
                                    [],
                                    null,
                                    $this->createMock(ConstraintViolation::class)
                                ),
                            ]
                        ),
                    ]
                ),
                'expectedErrors' => [],
            ],
            'TransitionIsAllowed errors' => [
                'errorIterator' => new FormErrorIterator(
                    $this->createMock(FormInterface::class),
                    [new FormError('sample_error', null, [], null, $transitionIsAllowedConstraintViolation)]
                ),
                'expectedErrors' => ['sample_error'],
            ],
            'double TransitionIsAllowed errors' => [
                'errorIterator' => new FormErrorIterator(
                    $this->createMock(FormInterface::class),
                    [
                        new FormError('sample_error', null, [], null, $transitionIsAllowedConstraintViolation),
                        new FormError('sample_error', null, [], null, $transitionIsAllowedConstraintViolation),
                    ]
                ),
                'expectedErrors' => ['sample_error'],
            ],
        ];
    }
}
