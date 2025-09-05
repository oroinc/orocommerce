<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\ActualizeLineItemsByUnpaidSubordersAction;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class ActualizeLineItemsByUnpaidSubordersActionTest extends TestCase
{
    private MockObject&ContextAccessor $contextAccessor;

    private MockObject&PaymentStatusManager $paymentStatusManager;

    private MockObject&PaymentStatusProviderInterface $paymentStatusProvider;

    private MockObject&CheckoutLineItemsProvider $checkoutLineItemsProvider;

    private ActualizeLineItemsByUnpaidSubordersAction $action;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);

        $this->action = new ActualizeLineItemsByUnpaidSubordersAction(
            $this->contextAccessor,
            $this->paymentStatusProvider,
            $this->checkoutLineItemsProvider
        );
        $this->action->setPaymentStatusManager($this->paymentStatusManager);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    private function getCheckout(array $lineItems): Checkout
    {
        $checkout = new Checkout();
        $checkout->setLineItems(new ArrayCollection($lineItems));

        return $checkout;
    }

    private function getCheckoutLineItem(string $sku): CheckoutLineItem
    {
        $lineItem = new CheckoutLineItem();
        $lineItem->setProductSku($sku);

        return $lineItem;
    }

    private function getOrder(array $lineItems = []): Order
    {
        $order = new Order();
        $order->setLineItems(new ArrayCollection($lineItems));

        return $order;
    }

    private function getOrderLineItem(string $sku): OrderLineItem
    {
        $lineItem = new OrderLineItem();
        $lineItem->setProductSku($sku);

        return $lineItem;
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     */
    public function testInitializeException(array $options, string $exceptionMessage): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $this->action->initialize($options);
    }

    public function invalidOptionsDataProvider(): array
    {
        return [
            'Empty options' => [
                [],
                '"order" parameter is required'
            ],
            'Absent "order" option ' => [
                ['checkout' => new PropertyPath('checkout')],
                '"order" parameter is required'
            ],
            'Absent "checkout" option' => [
                ['order' => new PropertyPath('order')],
                '"checkout" parameter is required'
            ]
        ];
    }

    public function testExecuteWhenPartialPaidAction(): void
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = $this->getCheckout([
            $this->getCheckoutLineItem('SKU1'),
            $this->getCheckoutLineItem('SKU2'),
            $this->getCheckoutLineItem('SKU3')
        ]);

        $subOrder1 = $this->getOrder([$this->getOrderLineItem('SKU1'), $this->getOrderLineItem('SKU2')]);
        $subOrder2 = $this->getOrder([$this->getOrderLineItem('SKU3')]);
        $order = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusManager->expects(self::exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                (new PaymentStatus())->setPaymentStatus(PaymentStatuses::AUTHORIZED),
                (new PaymentStatus())->setPaymentStatus(PaymentStatuses::PENDING)
            );

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getProductSkusWithDifferences')
            ->with(
                $subOrder2->getLineItems(),
                $checkout->getLineItems()
            )
            ->willReturn(['SKU3']);

        $this->action->initialize([
            'checkout' => $checkoutOption,
            'order' => $orderOption
        ]);
        $this->action->execute($context);

        self::assertFalse($checkout->getLineItems()->isEmpty());

        $skus = $checkout->getLineItems()->map(fn (CheckoutLineItem $lineItem) => $lineItem->getProductSku());
        self::assertEqualsCanonicalizing(['SKU1', 'SKU2'], $skus->toArray());
    }

    public function testExecuteWhenNotPaidAction(): void
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = $this->getCheckout([
            $this->getCheckoutLineItem('SKU1'),
            $this->getCheckoutLineItem('SKU2')
        ]);

        $subOrder1 = $this->getOrder([$this->getOrderLineItem('SKU1')]);
        $subOrder2 = $this->getOrder([$this->getOrderLineItem('SKU2')]);
        $order = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusManager->expects(self::exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                (new PaymentStatus())->setPaymentStatus(PaymentStatuses::AUTHORIZED),
                (new PaymentStatus())->setPaymentStatus(PaymentStatuses::AUTHORIZED)
            );

        $this->checkoutLineItemsProvider->expects(self::never())
            ->method('getProductSkusWithDifferences');

        $this->action->initialize([
            'checkout' => $checkoutOption,
            'order' => $orderOption
        ]);
        $this->action->execute($context);

        self::assertTrue($checkout->getLineItems()->isEmpty());
    }

    public function testExecuteWithNullPaymentStatusManagerPartialPaid(): void
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = $this->getCheckout([
            $this->getCheckoutLineItem('SKU1'),
            $this->getCheckoutLineItem('SKU2'),
            $this->getCheckoutLineItem('SKU3')
        ]);

        $subOrder1 = $this->getOrder([$this->getOrderLineItem('SKU1'), $this->getOrderLineItem('SKU2')]);
        $subOrder2 = $this->getOrder([$this->getOrderLineItem('SKU3')]);
        $order = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusProvider->expects(self::exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatuses::AUTHORIZED,
                PaymentStatuses::PENDING
            );

        $this->checkoutLineItemsProvider->expects(self::once())
            ->method('getProductSkusWithDifferences')
            ->with(
                $subOrder2->getLineItems(),
                $checkout->getLineItems()
            )
            ->willReturn(['SKU3']);

        $this->action->setPaymentStatusManager(null);
        $this->action->initialize([
            'checkout' => $checkoutOption,
            'order' => $orderOption
        ]);
        $this->action->execute($context);

        self::assertFalse($checkout->getLineItems()->isEmpty());

        $skus = $checkout->getLineItems()->map(fn (CheckoutLineItem $lineItem) => $lineItem->getProductSku());
        self::assertEqualsCanonicalizing(['SKU1', 'SKU2'], $skus->toArray());
    }

    public function testExecuteWithNullPaymentStatusManagerAllPaid(): void
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = $this->getCheckout([
            $this->getCheckoutLineItem('SKU1'),
            $this->getCheckoutLineItem('SKU2')
        ]);

        $subOrder1 = $this->getOrder([$this->getOrderLineItem('SKU1')]);
        $subOrder2 = $this->getOrder([$this->getOrderLineItem('SKU2')]);
        $order = new Order();
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusProvider->expects(self::exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatuses::AUTHORIZED,
                PaymentStatuses::AUTHORIZED
            );

        $this->checkoutLineItemsProvider->expects(self::never())
            ->method('getProductSkusWithDifferences');

        $this->action->setPaymentStatusManager(null);
        $this->action->initialize([
            'checkout' => $checkoutOption,
            'order' => $orderOption
        ]);
        $this->action->execute($context);

        self::assertTrue($checkout->getLineItems()->isEmpty());
    }
}
