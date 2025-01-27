<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\VerifyPayment;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VerifyPaymentTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private ValidatorInterface|MockObject $validator;

    private VerifyPayment $verifyPayment;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->verifyPayment = new VerifyPayment($this->actionExecutor);
        $this->verifyPayment->setValidator($this->validator);
    }

    /**
     * @dataProvider preConditionDataProvider
     */
    public function testIsPreConditionAllowed(
        bool $isCompleted,
        bool $paymentInProgress,
        bool $isAjaxCheckoutWidget,
        bool $isContinueToOrderReviewTransition,
        ?string $paymentMethod,
        bool $isPaymentRedirectRequired,
        bool $validationResult,
        bool $expected,
        array $expectedErrors
    ): void {
        $errors = new ArrayCollection();

        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();

        $checkout->setCompleted($isCompleted);
        $checkout->setPaymentMethod($paymentMethod);
        $workflowData = new WorkflowData([
            'payment_in_progress' => $paymentInProgress
        ]);

        $workflowItem->setEntity($checkout);
        $workflowItem->setData($workflowData);

        $this->actionExecutor->expects(self::any())
            ->method('evaluateExpression')
            ->willReturnMap([
                [
                    'check_request',
                    ['is_ajax' => true, 'expected_key' => '_wid', 'expected_value' => 'ajax_checkout'],
                    null,
                    null,
                    $isAjaxCheckoutWidget
                ],
                [
                    'check_request',
                    ['is_ajax' => true, 'expected_key' => 'transition', 'expected_value' => 'continue_to_order_review'],
                    null,
                    null,
                    $isContinueToOrderReviewTransition
                ],
                [
                    'require_payment_redirect',
                    ['payment_method' => $paymentMethod],
                    null,
                    null,
                    $isPaymentRedirectRequired
                ],
            ]);

        $violationsArray = [];
        if (!$validationResult) {
            $violation = $this->createMock(ConstraintViolationInterface::class);
            $violation->expects(self::once())
                ->method('getMessageTemplate')
                ->willReturn('error1');
            $violationsArray[] = $violation;
        }
        $violations = new ConstraintViolationList($violationsArray);
        $this->validator->expects(self::any())
            ->method('validate')
            ->with($checkout, null, 'checkout_verify_payment')
            ->willReturn($violations);

        $result = $this->verifyPayment->isPreConditionAllowed($workflowItem, $errors);

        self::assertSame($expected, $result);
        self::assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function preConditionDataProvider(): array
    {
        return [
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'validationResult' => true,
                'expected' => true,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => true,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'validationResult' => true,
                'expected' => false,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => true,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'validationResult' => true,
                'expected' => false,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => true,
                'isContinueToOrderReviewTransition' => true,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'validationResult' => true,
                'expected' => false,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => null,
                'isPaymentRedirectRequired' => true,
                'validationResult' => true,
                'expected' => false,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => false,
                'validationResult' => true,
                'expected' => false,
                'expectedErrors' => []
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'validationResult' => false,
                'expected' => false,
                'expectedErrors' => [
                    ['message' => 'error1', 'parameters' => []],
                ]
            ]
        ];
    }
}
