<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\CreateOrder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use Oro\Component\Action\Condition\ExtendableCondition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateOrderTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CheckoutPaymentContextProvider|MockObject $paymentContextProvider;
    private OrderActionsInterface|MockObject $orderActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;
    private PaymentMethodActionsInterface|MockObject $paymentMethodActions;
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private WorkflowManager|MockObject $workflowManager;

    private CreateOrder $createOrder;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->paymentContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);
        $this->orderActions = $this->createMock(OrderActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->paymentMethodActions = $this->createMock(PaymentMethodActionsInterface::class);
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->createOrder = new CreateOrder(
            $this->actionExecutor,
            $this->paymentContextProvider,
            $this->orderActions,
            $this->checkoutActions,
            $this->baseContinueTransition,
            $this->updateShippingPrice,
            $this->paymentMethodActions,
            $this->customerUserActions,
            $this->workflowManager
        );
    }

    /**
     * @dataProvider preConditionsDataProvider
     */
    public function testIsPreConditionAllowednotAllowed(
        bool $hasPaymentMethods,
        bool $hasShippingMethods,
        bool $baseAllowed,
        bool $isAllowedByEventListeners,
        ?int $workflowItemId,
        bool $expected,
        array $expectedErrors = []
    ): void {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setShippingMethod('shipping_method');
        $workflowData = new WorkflowData();
        $workflowResult = new WorkflowResult();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult, $workflowItemId);

        $paymentContext = $this->createMock(PaymentContext::class);
        $this->paymentContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap([
                ['has_applicable_payment_methods', ['context' => $paymentContext], null, null, $hasPaymentMethods],
                ['shipping_method_has_enabled_shipping_rules', ['shipping_method'], null, null, $hasShippingMethods],
                [
                    ExtendableCondition::NAME,
                    [
                        'events' => ['extendable_condition.pre_order_create'],
                        'eventData' => ['checkout' => $checkout]
                    ],
                    $errors,
                    null,
                    $isAllowedByEventListeners
                ],
                ['check_request', ['is_ajax' => true], null, null, true],
                ['check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'], null, null, true]
            ]);
        $this->actionExecutor->expects($this->never())
            ->method('executeAction')
            ->with('flash_message');

        $this->checkoutActions->expects($this->once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'save_state')
            ->willReturn('http://example.com/save_state');

        $this->baseContinueTransition->expects($this->any())
            ->method('isPreConditionAllowed')
            ->willReturn($baseAllowed);

        $this->assertSame($expected, $this->createOrder->isPreConditionAllowed($workflowItem, $errors));
        $this->assertEquals('http://example.com/save_state', $workflowResult->offsetGet('saveStateUrl'));

        if ($expectedErrors) {
            $this->assertNotEmpty($errors);
            $this->assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
        }
    }

    public static function preConditionsDataProvider(): array
    {
        return [
            'no payment methods' => [
                false,
                true,
                true,
                true,
                1,
                false,
                [['message' => 'oro.checkout.workflow.condition.payment_method_was_not_selected.message']]
            ],
            'no shipping methods' => [
                true,
                false,
                true,
                true,
                1,
                false,
                [['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']]
            ],
            'base checks are not passed' => [true, true, false, true, 1, false],
            'no workflow item id' => [true, true, true, false, null, false],
            'allowed' => [true, true, true, true, 1, true]
        ];
    }

    public function testIsPreConditionAllowedShowPaymentNotification(): void
    {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setShippingMethod('shipping_method');
        $workflowResult = new WorkflowResult([
            'extendableConditionPreOrderCreate' => true
        ]);

        // Important for payment notification check START >
        $checkout->setCompleted(false);
        $workflowData = new WorkflowData([
            'payment_in_progress' => true
        ]);
        // < END

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $paymentContext = $this->createMock(PaymentContext::class);
        $this->paymentContextProvider->expects($this->once())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap([
                ['has_applicable_payment_methods', ['context' => $paymentContext], null, null, true],
                ['shipping_method_has_enabled_shipping_rules', ['shipping_method'], null, null, true],
                [
                    ExtendableCondition::NAME,
                    [
                        'events' => ['extendable_condition.before_order_create'],
                        'eventData' => ['checkout' => $checkout]
                    ],
                    $errors,
                    'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message',
                    true
                ],
                // Next expressions are important for payment notification check
                ['check_request', ['is_ajax' => true], null, null, true],
                ['check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'], null, null, false]
            ]);
        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.checkout.workflow.condition.payment_has_not_been_processed.message',
                    'type' => 'warning'
                ]
            );

        $this->checkoutActions->expects($this->once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'save_state')
            ->willReturn('http://example.com/save_state');

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->willReturn(true);

        $this->assertTrue($this->createOrder->isPreConditionAllowed($workflowItem, $errors));
        $this->assertEquals('http://example.com/save_state', $workflowResult->offsetGet('saveStateUrl'));
    }

    /**
     * @dataProvider conditionsDataProvider
     */
    public function testIsConditionAllowed(
        bool $isSupportedRequest,
        bool $isConsentsAccepted,
        bool $isAddressValid,
        ?string $shippingMethod,
        bool $isPaymentMethodApplicable,
        bool $isAcceptedByListeners,
        bool $expected,
        array $expectedErrors = []
    ): void {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $workflowData = new WorkflowData();
        $workflowResult = new WorkflowResult();
        $errors = new ArrayCollection();

        $checkout->setShippingMethod($shippingMethod);
        $checkout->setPaymentMethod('payment_method');

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $paymentContext = $this->createMock(PaymentContext::class);
        $this->paymentContextProvider->expects($this->any())
            ->method('getContext')
            ->with($checkout)
            ->willReturn($paymentContext);

        $this->actionExecutor->expects($this->any())
            ->method('evaluateExpression')
            ->willReturnMap([
                [
                    'check_request',
                    ['is_ajax' => true, 'expected_key' => '_wid', 'expected_value' => 'ajax_checkout'],
                    null,
                    null,
                    $isSupportedRequest
                ],
                ['is_consents_accepted', ['acceptedConsents' => null], null, null, $isConsentsAccepted],
                ['validate_checkout_addresses', [$checkout], $errors, null, $isAddressValid],
                [
                    'payment_method_applicable',
                    [
                        'context' => $paymentContext,
                        'payment_method' => 'payment_method'
                    ],
                    null,
                    null,
                    $isPaymentMethodApplicable
                ],
                [
                    ExtendableCondition::NAME,
                    [
                        'events' => ['extendable_condition.before_order_create'],
                        'eventData' => ['checkout' => $checkout]
                    ],
                    $errors,
                    'oro.checkout.workflow.b2b_flow_checkout.transition.place_order.condition.extendable.message',
                    $isAcceptedByListeners
                ]
            ]);

        $this->assertSame($expected, $this->createOrder->isConditionAllowed($workflowItem, $errors));

        if ($expectedErrors) {
            $this->assertNotEmpty($errors);
            $this->assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
        }
    }

    public static function conditionsDataProvider(): array
    {
        return [
            'allowed' => [true, true, true, 'shipping_method', true, true, true],
            'not supported request' => [
                false,
                true,
                true,
                'shipping_method',
                true,
                true,
                false,
                [['message' => 'oro.checkout.workflow.condition.invalid_request.message']]
            ],
            'not accepted consents' => [
                true,
                false,
                true,
                'shipping_method',
                true,
                true,
                false,
                [
                    [
                        'message' => 'oro.checkout.workflow.condition.' .
                            'required_consents_should_be_checked_on_single_page_checkout.message'
                    ]
                ]
            ],
            'not valid address' => [true, true, false, 'shipping_method', true, true, false],
            'no shipping method' => [
                true,
                true,
                true,
                null,
                true,
                true,
                false,
                [['message' => 'oro.checkout.workflow.condition.shipping_method_is_not_available.message']]
            ],
            'no payment method' => [true, true, true, 'shipping_method', false, true, false],
            'not accepted by listeners' => [true, true, true, 'shipping_method', true, false, false],
        ];
    }

    public function testExecuteWithPassedPaymentValidate(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $billingAddress = new OrderAddress();
        $checkout = new Checkout();
        $checkout->setBillingAddress($billingAddress);
        $consents = [$this->createMock(Consent::class)];
        $workflowData = new WorkflowData([
            'customerConsents' => $consents,
            'email' => 'test@example.com',
            'additional_data' => 'data',
            'payment_validate' => true,
            'payment_save_for_later' => false
        ]);
        $workflowResult = new WorkflowResult();
        $order = new Order();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects($this->once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects($this->once())
            ->method('validate')
            ->with(
                $checkout,
                'http://example.com/purchase',
                'http://example.com/payment_error',
                'data',
                false
            )
            ->willReturn(['successful' => true]);
        $this->paymentMethodActions->expects($this->never())
            ->method('isPaymentMethodSupportsValidate');

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects($this->exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        $this->assertTrue($workflowResult->offsetGet('responseData')['successful']);
    }

    public function testExecuteWithFailedPaymentValidatePaymentMethodDoesNotSupportsValidation(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $billingAddress = new OrderAddress();
        $checkout = new Checkout();
        $checkout->setBillingAddress($billingAddress);
        $consents = [$this->createMock(Consent::class)];
        $workflowData = new WorkflowData([
            'customerConsents' => $consents,
            'email' => 'test@example.com',
            'additional_data' => 'data',
            'payment_validate' => true,
            'payment_save_for_later' => false
        ]);
        $workflowResult = new WorkflowResult();
        $order = new Order();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects($this->once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects($this->once())
            ->method('validate')
            ->with(
                $checkout,
                'http://example.com/purchase',
                'http://example.com/payment_error',
                'data',
                false
            )
            ->willReturn(['successful' => false]);
        $this->paymentMethodActions->expects($this->any())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects($this->exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        $this->assertFalse($workflowResult->offsetGet('responseData')['successful']);
        $this->assertNull($workflowResult->offsetGet('updateCheckoutState'));
    }

    public function testExecuteWithFailedPaymentValidatePaymentMethodDoesSupportsValidation(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $billingAddress = new OrderAddress();
        $checkout = new Checkout();
        $checkout->setBillingAddress($billingAddress);
        $consents = [$this->createMock(Consent::class)];
        $workflowData = new WorkflowData([
            'customerConsents' => $consents,
            'email' => 'test@example.com',
            'additional_data' => 'data',
            'payment_validate' => true,
            'payment_save_for_later' => false
        ]);
        $workflowResult = new WorkflowResult();
        $order = new Order();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects($this->once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects($this->once())
            ->method('validate')
            ->with(
                $checkout,
                'http://example.com/purchase',
                'http://example.com/payment_error',
                'data',
                false
            )
            ->willReturn(['successful' => false]);
        $this->paymentMethodActions->expects($this->any())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(true);

        $this->workflowManager->expects($this->never())
            ->method('transitIfAllowed');

        $this->checkoutActions->expects($this->exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        $this->assertFalse($workflowResult->offsetGet('responseData')['successful']);
        $this->assertTrue($workflowResult->offsetGet('updateCheckoutState'));
    }

    public function testExecuteWithoutPaymentValidation(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $billingAddress = new OrderAddress();
        $checkout = new Checkout();
        $checkout->setBillingAddress($billingAddress);
        $consents = [$this->createMock(Consent::class)];
        $workflowData = new WorkflowData([
            'customerConsents' => $consents,
            'email' => 'test@example.com',
            'additional_data' => 'data',
            'payment_validate' => false,
            'payment_save_for_later' => false
        ]);
        $workflowResult = new WorkflowResult();
        $order = new Order();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects($this->once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects($this->once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects($this->never())
            ->method('validate');
        $this->paymentMethodActions->expects($this->any())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->workflowManager->expects($this->once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects($this->never())
            ->method('getCheckoutUrl');

        $this->createOrder->execute($workflowItem);

        $this->assertNull($workflowResult->offsetGet('responseData'));
        $this->assertNull($workflowResult->offsetGet('updateCheckoutState'));
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
