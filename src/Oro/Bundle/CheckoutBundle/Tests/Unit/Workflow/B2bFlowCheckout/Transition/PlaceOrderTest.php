<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\PlaceOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PlaceOrderTest extends TestCase
{
    use EntityTrait;

    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutPaymentContextProvider|MockObject $paymentContextProvider;
    private OrderActionsInterface|MockObject $orderActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private ConfigProvider|MockObject $configProvider;
    private SplitOrderActionsInterface|MockObject $splitOrderActions;
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private CheckoutLineItemsConverter|MockObject $checkoutLineItemsConverter;
    private PlaceOrder $placeOrder;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->orderActions = $this->createMock(OrderActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->splitOrderActions = $this->createMock(SplitOrderActionsInterface::class);
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->checkoutLineItemsConverter = $this->createMock(CheckoutLineItemsConverter::class);

        $this->placeOrder = new PlaceOrder(
            $this->actionExecutor,
            $this->paymentContextProvider,
            $this->orderActions,
            $this->checkoutActions,
            $this->baseContinueTransition,
            $this->configProvider,
            $this->splitOrderActions,
            $this->shippingMethodActions
        );
        $this->placeOrder->setCheckoutLineItemsConverter($this->checkoutLineItemsConverter);
    }

    /**
     * @dataProvider preConditionDataProvider
     */
    public function testIsPreConditionAllowed(
        bool $hasApplicableShippingRules,
        bool $isPaymentMethodApplicable,
        bool $expected,
        array $expectedErrors
    ): void {
        $workflowItem = $this->getEntity(WorkflowItem::class, ['id' => 1]);
        $workflowItem->getResult()->offsetSet('extendableConditionPreOrderCreate', true);
        $checkout = new Checkout();
        $checkout->setPaymentMethod('payment_term');
        $errors = new ArrayCollection();
        $data = new WorkflowData([
            'payment_in_progress' => false,
            'line_items_shipping_methods' => ['method1'],
            'line_item_groups_shipping_methods' => ['group1' => ['method2']]
        ]);
        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->shippingMethodActions->expects(self::once())
            ->method('actualizeShippingMethods')
            ->with(
                $checkout,
                ['method1'],
                ['group1' => ['method2']]
            );

        $this->shippingMethodActions->expects(self::any())
            ->method('hasApplicableShippingRules')
            ->with($checkout, $errors)
            ->willReturn($hasApplicableShippingRules);

        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->paymentContextProvider->expects(self::any())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects(self::any())
            ->method('evaluateExpression')
            ->willReturnMap([
                [
                    'payment_method_applicable',
                    [
                        'context' => $paymentContext,
                        'payment_method' => 'payment_term'
                    ],
                    null,
                    null,
                    $isPaymentMethodApplicable
                ]
            ]);

        $this->baseContinueTransition->expects(self::any())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        $this->assertSame($expected, $this->placeOrder->isPreConditionAllowed($workflowItem, $errors));
        $this->assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
    }

    public static function preConditionDataProvider(): array
    {
        return [
            [true, true, true, []],
            [false, true, false, []],
            [
                true,
                false,
                false,
                [['message' => 'oro.checkout.workflow.condition.payment_method_is_not_applicable.message']]
            ],
        ];
    }

    public function testExecuteWithSubOrders(): void
    {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();
        $order = new Order();

        $groupedLineItems = [1, 2];
        $data = new WorkflowData(['grouped_line_items' => $groupedLineItems]);

        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->configProvider->expects(self::exactly(2))
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(true);

        $this->splitOrderActions->expects(self::once())
            ->method('createChildOrders')
            ->with($checkout, $order, $groupedLineItems);

        $this->checkoutActions->expects(self::any())
            ->method('getCheckoutUrl')
            ->willReturnMap([
                [$checkout, 'back_to_shipping_address_on_fail_address', 'back_to_shipping_address_on_fail_address_url'],
                [$checkout, 'paid_partially', 'paid_partially_url']
            ]);

        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'failedShippingAddressUrl' => 'back_to_shipping_address_on_fail_address_url',
                    'additionalData' => null,
                    'email' => null
                ]
            )
            ->willReturn(['responseData' => []]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => [],
                        'email' => null
                    ]
                ]
            );

        $this->checkoutLineItemsConverter->expects(self::exactly(2))
            ->method('setReuseLineItems')
            ->withConsecutive([true], [false]);

        $this->placeOrder->execute($workflowItem);

        $this->assertTrue($data->offsetGet('payment_in_progress'));
        $this->assertSame($order, $data->offsetGet('order'));
    }

    public function testExecutePaymentMethodSupportsValidation(): void
    {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();
        $order = new Order();

        $groupedLineItems = null;
        $data = new WorkflowData(['grouped_line_items' => $groupedLineItems]);

        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->configProvider->expects(self::any())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->splitOrderActions->expects($this->never())
            ->method('createChildOrders');

        $this->checkoutActions->expects(self::any())
            ->method('getCheckoutUrl')
            ->willReturnMap([
                [$checkout, 'back_to_shipping_address_on_fail_address', 'back_to_shipping_address_on_fail_address_url'],
                [$checkout, 'paid_partially', 'paid_partially_url']
            ]);

        $purchaseResult = [
            'successUrl' => 'url1',
            'purchaseSuccessful' => true,
            'paymentMethodSupportsValidation' => true
        ];
        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'failedShippingAddressUrl' => 'back_to_shipping_address_on_fail_address_url',
                    'additionalData' => null,
                    'email' => null
                ]
            )
            ->willReturn(['responseData' => $purchaseResult]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => $purchaseResult,
                        'email' => null
                    ]
                ]
            );

        $this->placeOrder->execute($workflowItem);

        $this->assertTrue($data->offsetGet('payment_in_progress'));
        $this->assertSame($order, $data->offsetGet('order'));
        $this->assertEquals('url1', $workflowItem->getResult()->offsetGet('redirectUrl'));
    }

    public function testExecutePaymentMethodDoesNotSupportsValidation(): void
    {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();
        $order = new Order();

        $groupedLineItems = null;
        $data = new WorkflowData(['grouped_line_items' => $groupedLineItems]);

        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->configProvider->expects(self::any())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->splitOrderActions->expects($this->never())
            ->method('createChildOrders');

        $this->checkoutActions->expects(self::any())
            ->method('getCheckoutUrl')
            ->willReturnMap([
                [$checkout, 'back_to_shipping_address_on_fail_address', 'back_to_shipping_address_on_fail_address_url'],
                [$checkout, 'paid_partially', 'paid_partially_url']
            ]);

        $purchaseResult = [
            'successUrl' => 'url1',
            'purchaseSuccessful' => true,
            'paymentMethodSupportsValidation' => false
        ];
        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'failedShippingAddressUrl' => 'back_to_shipping_address_on_fail_address_url',
                    'additionalData' => null,
                    'email' => null
                ]
            )
            ->willReturn(['responseData' => $purchaseResult]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => $purchaseResult,
                        'email' => null
                    ]
                ]
            );

        $this->placeOrder->execute($workflowItem);

        $this->assertTrue($data->offsetGet('payment_in_progress'));
        $this->assertSame($order, $data->offsetGet('order'));
        $this->assertNull($workflowItem->getResult()->offsetGet('redirectUrl'));
    }

    public function testExecutePurchasePartial(): void
    {
        $workflowItem = new WorkflowItem();
        $checkout = new Checkout();
        $order = new Order();

        $groupedLineItems = null;
        $data = new WorkflowData(['grouped_line_items' => $groupedLineItems]);

        $workflowItem->setData($data);
        $workflowItem->setEntity($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->configProvider->expects(self::any())
            ->method('isCreateSubOrdersForEachGroupEnabled')
            ->willReturn(false);

        $this->splitOrderActions->expects($this->never())
            ->method('createChildOrders');

        $purchaseResult = [
            'successUrl' => 'url1',
            'purchaseSuccessful' => true,
            'paymentMethodSupportsValidation' => false,
            'purchasePartial' => true
        ];

        $this->checkoutActions->expects(self::any())
            ->method('getCheckoutUrl')
            ->willReturnMap([
                [$checkout, 'back_to_shipping_address_on_fail_address', 'back_to_shipping_address_on_fail_address_url'],
                [$checkout, 'paid_partially', 'paid_partially_url']
            ]);

        $this->checkoutActions->expects(self::once())
            ->method('purchase')
            ->with(
                $checkout,
                $order,
                [
                    'failedShippingAddressUrl' => 'back_to_shipping_address_on_fail_address_url',
                    'additionalData' => null,
                    'email' => null
                ]
            )
            ->willReturn(['responseData' => $purchaseResult]);

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                ExtendableAction::NAME,
                [
                    'events' => ['extendable_action.finish_checkout'],
                    'eventData' => [
                        'order' => $order,
                        'checkout' => $checkout,
                        'responseData' => array_merge($purchaseResult, ['partiallyPaidUrl' => 'paid_partially_url']),
                        'email' => null
                    ]
                ]
            );

        $this->placeOrder->execute($workflowItem);

        $this->assertTrue($data->offsetGet('payment_in_progress'));
        $this->assertSame($order, $data->offsetGet('order'));
        $this->assertNull($workflowItem->getResult()->offsetGet('redirectUrl'));
    }
}
