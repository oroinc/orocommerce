<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderLineItemsNotEmptyInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\VerifyPayment;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VerifyPaymentTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private OrderLineItemsNotEmptyInterface|MockObject $orderLineItemsNotEmpty;

    private VerifyPayment $verifyPayment;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->orderLineItemsNotEmpty = $this->createMock(OrderLineItemsNotEmptyInterface::class);

        $this->verifyPayment = new VerifyPayment(
            $this->actionExecutor,
            $this->orderLineItemsNotEmpty
        );
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
        array $orderLineItemsNotEmptyResult,
        bool $expected,
        array $expectedErrors
    ): void {
        $errors = new ArrayCollection();

        $checkout = new Checkout();
        $workflowItem = new WorkflowItem();

        $checkout->setCompleted($isCompleted);
        $workflowData = new WorkflowData([
            'payment_in_progress' => $paymentInProgress,
            'payment_method' => $paymentMethod
        ]);

        $workflowItem->setEntity($checkout);
        $workflowItem->setData($workflowData);

        $this->actionExecutor->expects($this->any())
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

        $this->orderLineItemsNotEmpty->expects($this->any())
            ->method('execute')
            ->with($checkout)
            ->willReturn($orderLineItemsNotEmptyResult);

        $result = $this->verifyPayment->isPreConditionAllowed($workflowItem, $errors);

        $this->assertSame($expected, $result);
        $this->assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => true
                ],
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
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => false,
                    'orderLineItemsNotEmpty' => true
                ],
                'expected' => false,
                'expectedErrors' => [
                    ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.not_allow_rfp.message']
                ]
            ],
            [
                'isCompleted' => false,
                'paymentInProgress' => false,
                'isAjaxCheckoutWidget' => false,
                'isContinueToOrderReviewTransition' => false,
                'paymentMethod' => 'payment_term',
                'isPaymentRedirectRequired' => true,
                'orderLineItemsNotEmptyResult' => [
                    'orderLineItemsNotEmptyForRfp' => true,
                    'orderLineItemsNotEmpty' => false
                ],
                'expected' => false,
                'expectedErrors' => [
                    ['message' => 'oro.checkout.workflow.condition.order_line_items_not_empty.allow_rfp.message']
                ]
            ],
        ];
    }
}
