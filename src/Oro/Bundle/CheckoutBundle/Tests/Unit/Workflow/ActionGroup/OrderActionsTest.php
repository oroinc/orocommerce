<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\ActionGroup;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActions;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\OrderActions;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderActionsTest extends TestCase
{
    use EntityTrait;

    private AddressActions|MockObject $addressActions;
    private PaymentTermProviderInterface|MockObject $paymentTermProvider;
    private CheckoutLineItemsManager|MockObject $checkoutLineItemsManager;
    private MapperInterface|MockObject $mapper;
    private EntityPaymentMethodsProvider|MockObject $paymentMethodsProvider;
    private TotalHelper|MockObject $totalHelper;
    private ActionExecutor|MockObject $actionExecutor;
    private OrderActions $orderActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressActions = $this->createMock(AddressActions::class);
        $this->paymentTermProvider = $this->createMock(PaymentTermProviderInterface::class);
        $this->checkoutLineItemsManager = $this->createMock(CheckoutLineItemsManager::class);
        $this->mapper = $this->createMock(MapperInterface::class);
        $this->paymentMethodsProvider = $this->createMock(EntityPaymentMethodsProvider::class);
        $this->totalHelper = $this->createMock(TotalHelper::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->orderActions = new OrderActions(
            $this->addressActions,
            $this->paymentTermProvider,
            $this->checkoutLineItemsManager,
            $this->mapper,
            $this->paymentMethodsProvider,
            $this->totalHelper,
            $this->actionExecutor
        );
    }

    public function testPlaceOrder(): void
    {
        $checkout = new Checkout();
        $billingAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $billingAddressCopy = $this->createMock(OrderAddress::class);
        $shippingAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $shippingAddressCopy = $this->createMock(OrderAddress::class);
        $customer = $this->createMock(Customer::class);

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customer);

        $order = new Order();

        $this->prepareCheckout($checkout, $billingAddress, $shippingAddress, $customerUser);

        $this->assertCreateOrderByCheckoutCalls(
            $billingAddress,
            $shippingAddress,
            $billingAddressCopy,
            $shippingAddressCopy,
            $checkout,
            $order
        );
        $this->assertFlushOrder($order);

        $result = $this->orderActions->placeOrder($checkout);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertSame($customerUser, $order->getCustomerUser());
        $this->assertSame($customer, $order->getCustomer());
        $this->assertSame($order, $checkout->getOrder());
    }

    public function testFlushOrder(): void
    {
        $order = $this->createMock(Order::class);
        $this->assertFlushOrder($order);

        $this->orderActions->flushOrder($order);
    }

    public function testCreateOrderByCheckout(): void
    {
        $checkout = new Checkout();
        $billingAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $billingAddressCopy = $this->createMock(OrderAddress::class);
        $shippingAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $shippingAddressCopy = $this->createMock(OrderAddress::class);
        $customer = $this->createMock(Customer::class);

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customer);

        $order = new Order();

        $this->prepareCheckout($checkout, $billingAddress, $shippingAddress, $customerUser);

        $this->assertCreateOrderByCheckoutCalls(
            $billingAddress,
            $shippingAddress,
            $billingAddressCopy,
            $shippingAddressCopy,
            $checkout,
            $order
        );

        $result = $this->orderActions->createOrderByCheckout($checkout, $billingAddress, $shippingAddress);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testCreateOrderByCheckoutWithOrder(): void
    {
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $billingAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $billingAddressCopy = $this->createMock(OrderAddress::class);
        $shippingAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $shippingAddressCopy = $this->createMock(OrderAddress::class);
        $customer = $this->createMock(Customer::class);

        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser->expects($this->any())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->prepareCheckout($checkout, $billingAddress, $shippingAddress, $customerUser);

        $this->assertCreateOrderByCheckoutWithOrderCalls(
            $billingAddress,
            $shippingAddress,
            $billingAddressCopy,
            $shippingAddressCopy,
            $checkout,
            $order
        );

        $result = $this->orderActions->createOrderByCheckout($checkout, $billingAddress, $shippingAddress);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testSendConfirmationEmailWithImmediateEmail(): void
    {
        $user = new User();
        $user->setEmail('owner@example.com');

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $registeredCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $checkout = new Checkout();
        $checkout->setRegisteredCustomerUser($registeredCustomerUser);

        $lineItems = new ArrayCollection([$this->createMock(OrderLineItem::class)]);
        $order = new Order();
        $order->setOwner($user);
        $order->setCustomerUser($customerUser);
        $order->setLineItems($lineItems);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'send_order_confirmation_email',
                [
                    'from' => ['email' => 'owner@example.com', 'name' => $user],
                    'to' => [$customerUser, $registeredCustomerUser],
                    'template' => 'order_confirmation_email',
                    'entity' => $order
                ]
            );

        $this->orderActions->setImmediateEmailLineItemsLimit(2);
        $this->orderActions->sendConfirmationEmail($checkout, $order);
    }

    public function testSendConfirmationEmailWithScheduledEmail(): void
    {
        $user = new User();
        $user->setEmail('owner@example.com');

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $registeredCustomerUser = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $checkout = new Checkout();
        $checkout->setRegisteredCustomerUser($registeredCustomerUser);

        $lineItems = new ArrayCollection([
            $this->createMock(OrderLineItem::class),
            $this->createMock(OrderLineItem::class)
        ]);
        $order = new Order();
        $order->setOwner($user);
        $order->setCustomerUser($customerUser);
        $order->setLineItems($lineItems);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'schedule_send_email_template',
                [
                    'from' => ['email' => 'owner@example.com', 'name' => $user],
                    'to' => [$customerUser, $registeredCustomerUser],
                    'template' => 'order_confirmation_email',
                    'entity' => $order
                ]
            );

        $this->orderActions->setImmediateEmailLineItemsLimit(1);
        $this->orderActions->sendConfirmationEmail($checkout, $order);
    }

    private function prepareCheckout(
        Checkout $checkout,
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress,
        CustomerUser $customerUser
    ): void {
        $shoppingList = new ShoppingList();
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects($this->any())
            ->method('getEntity')
            ->willReturn($shoppingList);

        $checkout->setBillingAddress($billingAddress);
        $checkout->setShippingAddress($shippingAddress);
        $checkout->setShipToBillingAddress(false);
        $checkout->setRegisteredCustomerUser($customerUser);
        $checkout->setSource($checkoutSource);
        $checkout->setPaymentMethod('term');
    }

    private function assertCreateOrderByCheckoutCalls(
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress,
        OrderAddress $billingAddressCopy,
        OrderAddress $shippingAddressCopy,
        Checkout $checkout,
        Order $order
    ): void {
        $this->addressActions->expects($this->exactly(2))
            ->method('duplicateOrderAddress')
            ->withConsecutive([$billingAddress], [$shippingAddress])
            ->willReturnOnConsecutiveCalls($billingAddressCopy, $shippingAddressCopy);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn('payment_term');

        $orderLineItems = new ArrayCollection([$this->createMock(OrderLineItem::class)]);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($orderLineItems);

        $this->mapper->expects($this->once())
            ->method('map')
            ->with(
                $checkout,
                [
                    'billingAddress' => $billingAddressCopy,
                    'shippingAddress' => $shippingAddressCopy,
                    'sourceEntityClass' => ShoppingList::class,
                    'paymentTerm' => 'payment_term',
                    'lineItems' => $orderLineItems
                ]
            )
            ->willReturn($order);

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['term']);
        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($order);
    }

    private function assertCreateOrderByCheckoutWithOrderCalls(
        OrderAddress $billingAddress,
        OrderAddress $shippingAddress,
        OrderAddress $billingAddressCopy,
        OrderAddress $shippingAddressCopy,
        Checkout $checkout,
        Order $order
    ): void {
        $this->addressActions->expects($this->exactly(2))
            ->method('duplicateOrderAddress')
            ->withConsecutive([$billingAddress], [$shippingAddress])
            ->willReturnOnConsecutiveCalls($billingAddressCopy, $shippingAddressCopy);

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn('payment_term');

        $orderLineItems = new ArrayCollection([$this->createMock(OrderLineItem::class)]);
        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($orderLineItems);

        $this->mapper->expects($this->never())
            ->method('map');

        $this->paymentMethodsProvider->expects($this->once())
            ->method('storePaymentMethodsToEntity')
            ->with($order, ['term']);
        $this->totalHelper->expects($this->once())
            ->method('fill')
            ->with($order);
    }

    private function assertFlushOrder(Order $order): void
    {
        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('flush_entity', [$order]);
    }
}
