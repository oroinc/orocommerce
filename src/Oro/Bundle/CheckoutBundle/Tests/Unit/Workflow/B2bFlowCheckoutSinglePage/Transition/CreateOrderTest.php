<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\PaymentMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\UpdateShippingPriceInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\CreateOrder;
use Oro\Bundle\ConsentBundle\Entity\Consent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private OrderActionsInterface|MockObject $orderActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private UpdateShippingPriceInterface|MockObject $updateShippingPrice;
    private PaymentMethodActionsInterface|MockObject $paymentMethodActions;
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private WorkflowManager|MockObject $workflowManager;
    private ValidatorInterface|MockObject $validator;

    private CreateOrder $createOrder;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->orderActions = $this->createMock(OrderActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->updateShippingPrice = $this->createMock(UpdateShippingPriceInterface::class);
        $this->paymentMethodActions = $this->createMock(PaymentMethodActionsInterface::class);
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->createOrder = new CreateOrder(
            $this->actionExecutor,
            $this->baseContinueTransition,
            $this->orderActions,
            $this->checkoutActions,
            $this->updateShippingPrice,
            $this->paymentMethodActions,
            $this->customerUserActions,
            $this->workflowManager
        );
        $this->createOrder->setValidator($this->validator);
    }

    /**
     * @dataProvider preConditionsDataProvider
     */
    public function testIsPreConditionAllowednotAllowed(
        bool $baseAllowed,
        ?int $workflowItemId,
        bool $expected
    ): void {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setShippingMethod('shipping_method');
        $workflowData = new WorkflowData();
        $workflowResult = new WorkflowResult();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult, $workflowItemId);

        $this->actionExecutor->expects(self::never())
            ->method('executeAction');

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'save_state')
            ->willReturn('http://example.com/save_state');

        $this->baseContinueTransition->expects(self::any())
            ->method('isPreConditionAllowed')
            ->willReturn($baseAllowed);

        self::assertSame($expected, $this->createOrder->isPreConditionAllowed($workflowItem, $errors));
        self::assertEquals('http://example.com/save_state', $workflowResult->offsetGet('saveStateUrl'));
    }

    public static function preConditionsDataProvider(): array
    {
        return [
            'base checks are not passed' => [false, 1, false],
            'no workflow item id' => [true, null, false],
            'allowed' => [true, 1, true]
        ];
    }

    public function testIsPreConditionAllowedShowPaymentNotification(): void
    {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setShippingMethod('shipping_method');
        $workflowResult = new WorkflowResult();

        // Important for payment notification check START >
        $checkout->setCompleted(false);
        $workflowData = new WorkflowData([
            'payment_in_progress' => true
        ]);
        // < END

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects(self::exactly(2))
            ->method('evaluateExpression')
            ->willReturnMap([
                // Next expressions are important for payment notification check
                ['check_request', ['is_ajax' => true], null, null, true],
                ['check_request', ['expected_key' => 'transition', 'expected_value' => 'purchase'], null, null, false]
            ]);
        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.checkout.workflow.condition.payment_has_not_been_processed.message',
                    'type' => 'warning'
                ]
            );

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'save_state')
            ->willReturn('http://example.com/save_state');

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->willReturn(true);

        self::assertTrue($this->createOrder->isPreConditionAllowed($workflowItem, $errors));
        self::assertEquals('http://example.com/save_state', $workflowResult->offsetGet('saveStateUrl'));
    }

    /**
     * @dataProvider conditionsDataProvider
     */
    public function testIsConditionAllowed(
        bool $isSupportedRequest,
        bool $isConsentsAccepted,
        bool $expected,
        array $expectedErrors = []
    ): void {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $workflowData = new WorkflowData();
        $workflowResult = new WorkflowResult();
        $errors = new ArrayCollection();

        $this->prepareWorkflowItem($workflowItem, $checkout, $workflowData, $workflowResult);

        $this->actionExecutor->expects(self::any())
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
            ]);

        self::assertSame($expected, $this->createOrder->isConditionAllowed($workflowItem, $errors));

        if ($expectedErrors) {
            self::assertNotEmpty($errors);
            self::assertEqualsCanonicalizing($expectedErrors, $errors->toArray());
        }
    }

    public static function conditionsDataProvider(): array
    {
        return [
            'allowed' => [true, true, true, []],
            'not supported request' => [
                false,
                true,
                false,
                [['message' => 'oro.checkout.workflow.condition.invalid_request.message']]
            ],
            'not accepted consents' => [
                true,
                false,
                false,
                [
                    [
                        'message' => 'oro.checkout.workflow.condition.' .
                            'required_consents_should_be_checked_on_single_page_checkout.message'
                    ]
                ]
            ]
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

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects(self::once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects(self::once())
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

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects(self::exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        self::assertTrue($workflowResult->offsetGet('responseData')['successful']);
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

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects(self::once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects(self::once())
            ->method('validate')
            ->with(
                $checkout,
                'http://example.com/purchase',
                'http://example.com/payment_error',
                'data',
                false
            )
            ->willReturn(['successful' => false]);
        $this->paymentMethodActions->expects(self::atLeastOnce())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(false);

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects(self::exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        self::assertFalse($workflowResult->offsetGet('responseData')['successful']);
        self::assertNull($workflowResult->offsetGet('updateCheckoutState'));
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

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects(self::once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects(self::once())
            ->method('validate')
            ->with(
                $checkout,
                'http://example.com/purchase',
                'http://example.com/payment_error',
                'data',
                false
            )
            ->willReturn(['successful' => false]);
        $this->paymentMethodActions->expects(self::atLeastOnce())
            ->method('isPaymentMethodSupportsValidate')
            ->with($checkout)
            ->willReturn(true);

        $this->workflowManager->expects(self::never())
            ->method('transitIfAllowed');

        $this->checkoutActions->expects(self::exactly(2))
            ->method('getCheckoutUrl')
            ->withConsecutive(
                [$checkout, 'purchase'],
                [$checkout, 'payment_error']
            )
            ->willReturnOnConsecutiveCalls('http://example.com/purchase', 'http://example.com/payment_error');

        $this->createOrder->execute($workflowItem);

        self::assertFalse($workflowResult->offsetGet('responseData')['successful']);
        self::assertTrue($workflowResult->offsetGet('updateCheckoutState'));
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

        $this->actionExecutor->expects(self::once())
            ->method('executeAction')
            ->with(
                'save_accepted_consents',
                ['acceptedConsents' => $consents]
            );

        $this->updateShippingPrice->expects(self::once())
            ->method('execute')
            ->with($checkout);

        $this->orderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->customerUserActions->expects(self::once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->paymentMethodActions->expects(self::never())
            ->method('validate');
        $this->paymentMethodActions->expects(self::never())
            ->method('isPaymentMethodSupportsValidate');

        $this->workflowManager->expects(self::once())
            ->method('transitIfAllowed')
            ->with($workflowItem, 'purchase');

        $this->checkoutActions->expects(self::never())
            ->method('getCheckoutUrl');

        $this->createOrder->execute($workflowItem);

        self::assertNull($workflowResult->offsetGet('responseData'));
        self::assertNull($workflowResult->offsetGet('updateCheckoutState'));
    }

    private function prepareWorkflowItem(
        WorkflowItem|MockObject $workflowItem,
        Checkout $checkout,
        WorkflowData $workflowData,
        WorkflowResult $workflowResult,
        ?int $workflowItemId = 1
    ): void {
        $workflowItem->expects(self::any())
            ->method('getId')
            ->willReturn($workflowItemId);
        $workflowItem->expects(self::any())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowItem->expects(self::any())
            ->method('getData')
            ->willReturn($workflowData);
        $workflowItem->expects(self::any())
            ->method('getResult')
            ->willReturn($workflowResult);
    }
}
