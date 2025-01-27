<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\SplitOrderActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\PlaceOrder;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Action\Action\ExtendableAction;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PlaceOrderTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private SplitOrderActionsInterface|MockObject $splitOrderActions;
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private ValidatorInterface|MockObject $validator;

    private PlaceOrder $placeOrder;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->splitOrderActions = $this->createMock(SplitOrderActionsInterface::class);
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->placeOrder = new PlaceOrder(
            $this->actionExecutor,
            $this->baseContinueTransition,
            $this->checkoutActions,
            $this->splitOrderActions,
            $this->shippingMethodActions
        );
        $this->placeOrder->setValidator($this->validator);
    }

    /**
     * @dataProvider preConditionDataProvider
     */
    public function testIsPreConditionAllowed(
        bool $isValidationPassed,
        bool $isBaseValidationPassed,
        bool $expected
    ): void {
        $workflowItem = new WorkflowItem();
        ReflectionUtil::setId($workflowItem, 1);
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

        $violationsArray = [];
        if (!$isValidationPassed) {
            $violationsArray[] = $this->createMock(ConstraintViolationInterface::class);
        }
        $violations = new ConstraintViolationList($violationsArray);
        $this->validator->expects(self::any())
            ->method('validate')
            ->with($checkout, null, 'checkout_order_create_pre_checks')
            ->willReturn($violations);

        $this->baseContinueTransition->expects(self::any())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn($isBaseValidationPassed);

        self::assertSame($expected, $this->placeOrder->isPreConditionAllowed($workflowItem, $errors));
    }

    public static function preConditionDataProvider(): array
    {
        return [
            [true, true, true],
            [true, false, false],
            [false, true, false],
            [false, false, false],
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

        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItems)
            ->willReturn($order);

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

        $this->placeOrder->execute($workflowItem);

        self::assertTrue($data->offsetGet('payment_in_progress'));
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

        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItems)
            ->willReturn($order);

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'back_to_shipping_address_on_fail_address')
            ->willReturn('back_to_shipping_address_on_fail_address_url');

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

        self::assertTrue($data->offsetGet('payment_in_progress'));
        self::assertEquals('url1', $workflowItem->getResult()->offsetGet('redirectUrl'));
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

        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItems)
            ->willReturn($order);

        $this->checkoutActions->expects(self::once())
            ->method('getCheckoutUrl')
            ->with($checkout, 'back_to_shipping_address_on_fail_address')
            ->willReturn('back_to_shipping_address_on_fail_address_url');

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

        self::assertTrue($data->offsetGet('payment_in_progress'));
        self::assertNull($workflowItem->getResult()->offsetGet('redirectUrl'));
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

        $this->splitOrderActions->expects(self::once())
            ->method('placeOrder')
            ->with($checkout, $groupedLineItems)
            ->willReturn($order);

        $purchaseResult = [
            'successUrl' => 'url1',
            'purchaseSuccessful' => true,
            'paymentMethodSupportsValidation' => false,
            'purchasePartial' => true
        ];

        $this->checkoutActions->expects(self::exactly(2))
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

        self::assertTrue($data->offsetGet('payment_in_progress'));
        self::assertNull($workflowItem->getResult()->offsetGet('redirectUrl'));
    }
}
