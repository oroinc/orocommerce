<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\Purchase;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Action\Condition\ExtendableCondition;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PurchaseTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private PaymentMethodActionsInterface|MockObject $paymentMethodActions;
    private CheckoutPaymentContextProvider|MockObject $paymentContextProvider;
    private PaymentTransactionProvider|MockObject $paymentTransactionProvider;
    private TransitionServiceInterface|MockObject $baseContinueTransition;

    private Purchase $purchase;

    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->paymentMethodActions = $this->createMock(PaymentMethodActionsInterface::class);
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->purchase = new Purchase(
            $this->actionExecutor,
            $this->checkoutActions,
            $this->shippingMethodActions,
            $this->paymentMethodActions,
            $this->paymentContextProvider,
            $this->paymentTransactionProvider,
            $this->baseContinueTransition
        );
    }

    public function testIsConditionAllowedReturnsFalseWhenCheckoutIsCompleted(): void
    {
        $checkout = new Checkout();
        $checkout->setCompleted(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseWhenPaymentMethodDoesNotSupportValidate(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setCompleted(false);
        $checkout->setPaymentMethod('payment_method');
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects($this->once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(true);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getActiveValidatePaymentTransaction')
            ->with('payment_method')
            ->willReturn(null);

        $this->assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseWhenPurchaseViaDirectUrlIsNotAllowed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setCompleted(false);
        $checkout->setPaymentMethod('payment_method');
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects($this->once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->actionExecutor->expects($this->once())
            ->method('evaluateExpression')
            ->with('check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'])
            ->willReturn(true);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn(false);

        $this->assertFalse($this->purchase->isConditionAllowed($workflowItem));
    }

    /**
     * @dataProvider conditionDataProvider
     */
    public function testIsConditionAllowed(
        bool $isBasePreconditionAllowed,
        bool $hasApplicableShippingRules,
        bool $hasPaymentMethods,
        bool $isAllowedByEventListeners,
        bool $expected
    ): void {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData(['order' => $order, 'checkout' => $checkout]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->paymentMethodActions->expects($this->once())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->baseContinueTransition->expects($this->any())
            ->method('isPreConditionAllowed')
            ->with($workflowItem)
            ->willReturn($isBasePreconditionAllowed);

        $this->shippingMethodActions->expects($this->any())
            ->method('hasApplicableShippingRules')
            ->with($checkout)
            ->willReturn($hasApplicableShippingRules);

        $paymentContext = $this->createMock(PaymentContext::class);
        $this->paymentContextProvider->expects($this->any())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);
        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap([
                [
                    'has_applicable_payment_methods',
                    [$paymentContext],
                    $errors,
                    'oro.checkout.workflow.condition.payment_method_is_not_applicable.message',
                    $hasPaymentMethods
                ],
                [
                    ExtendableCondition::NAME,
                    [
                        'events' => ['extendable_condition.before_order_create'],
                        'eventData' => [
                            'checkout' => $checkout,
                            'order' => $order,
                            ExtendableConditionEvent::CONTEXT_KEY => $workflowItem
                        ]
                    ],
                    $errors,
                    'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message',
                    $isAllowedByEventListeners
                ],
                ['check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'], null, null, true]
            ]);

        $this->assertSame($expected, $this->purchase->isConditionAllowed($workflowItem, $errors));
    }

    public static function conditionDataProvider(): array
    {
        return [
            'base precondition not allowed' => [false, true, true, true, false],
            'no applicable shipping rules' => [true, false, true, true, false],
            'no payment methods' => [true, true, false, true, false],
            'not allowed by event listeners' => [true, true, true, false, false],
            'allowed' => [true, true, true, true, true],
        ];
    }

    public function testExecuteSuccessful(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData([
            'order' => $order,
            'checkout' => $checkout,
            'additional_data' => 'additionalData',
            'email' => 'test@example.com',
        ]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->checkoutActions->expects($this->once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'additionalData' => 'additionalData',
                    'email' => 'test@example.com'
                ]
            )
            ->willReturn(['responseData' => ['purchaseSuccessful' => true]]);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => ['purchaseSuccessful' => true],
                        'email' => 'test@example.com',
                        ExtendableConditionEvent::CONTEXT_KEY => $workflowItem
                    ]
                ]
            );

        $this->checkoutActions->expects($this->once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'finish_checkout')
            ->willReturn('checkout_url');

        $workflowItem->expects($this->once())
            ->method('setRedirectUrl')
            ->with('checkout_url');

        $this->purchase->execute($workflowItem);
    }

    public function testExecuteFailed(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_method');
        $checkout->setCompleted(false);
        $order = new Order();

        $workflowResult = new WorkflowResult();
        $workflowData = new WorkflowData([
            'order' => $order,
            'checkout' => $checkout,
            'additional_data' => 'additionalData',
            'email' => 'test@example.com',
        ]);

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->checkoutActions->expects($this->once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'additionalData' => 'additionalData',
                    'email' => 'test@example.com'
                ]
            )
            ->willReturn(['responseData' => ['purchaseSuccessful' => false]]);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => ['purchaseSuccessful' => false],
                        'email' => 'test@example.com',
                        ExtendableConditionEvent::CONTEXT_KEY => $workflowItem
                    ]
                ]
            );

        $this->checkoutActions->expects($this->once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'payment_error')
            ->willReturn('payment_error_url');

        $workflowItem->expects($this->once())
            ->method('setRedirectUrl')
            ->with('payment_error_url');

        $this->purchase->execute($workflowItem);
    }

    private function prepareWorkflowItem(
        WorkflowItem|MockObject $workflowItem,
        Checkout $checkout,
        WorkflowData $workflowData,
        WorkflowResult $workflowResult,
        ?int $workflowItemId = 1
    ): void {
        $workflowItem->expects($this->any())
            ->method('getId')
            ->willReturn($workflowItemId);
        $workflowItem->expects($this->any())
            ->method('getEntity')
            ->willReturn($checkout);

        $workflowItem->expects($this->any())
            ->method('getData')
            ->willReturn($workflowData);

        $workflowItem->expects($this->any())
            ->method('getResult')
            ->willReturn($workflowResult);
    }
}
