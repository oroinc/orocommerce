<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action\MultiShipping;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\ActualizeLineItemsByUnpaidSubordersAction;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutLineItemsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProviderInterface;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class ActualizeLineItemsByUnpaidSubordersActionTest extends TestCase
{
    use EntityTrait;

    private ContextAccessor|MockObject $contextAccessor;
    private PaymentStatusProviderInterface|MockObject $paymentStatusProvider;
    private CheckoutLineItemsProvider|MockObject $checkoutLineItemsProvider;
    private EventDispatcher|MockObject $dispatcher;

    private ActualizeLineItemsByUnpaidSubordersAction $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProviderInterface::class);
        $this->checkoutLineItemsProvider = $this->createMock(CheckoutLineItemsProvider::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new ActualizeLineItemsByUnpaidSubordersAction(
            $this->contextAccessor,
            $this->paymentStatusProvider,
            $this->checkoutLineItemsProvider
        );
        $this->action->setDispatcher($this->dispatcher);
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

    public function testExecuteWhenPartialPaidAction()
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = new Checkout();
        $lineItem1 = new CheckoutLineItem();
        $lineItem1->setProductSku('SKU1');
        $lineItem2 = new CheckoutLineItem();
        $lineItem2->setProductSku('SKU2');
        $lineItem3 = new CheckoutLineItem();
        $lineItem3->setProductSku('SKU3');
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2, $lineItem3]));

        $order = $this->getEntity(Order::class, ['id' => 1]);
        $subOrder1 = $this->getEntity(Order::class, ['id' => 2]);
        $orderLineItem1 = new OrderLineItem();
        $orderLineItem1->setProductSku('SKU1');
        $orderLineItem2 = new OrderLineItem();
        $orderLineItem2->setProductSku('SKU2');
        $subOrder1->setLineItems(new ArrayCollection([$orderLineItem1, $orderLineItem2]));
        $subOrder2 = $this->getEntity(Order::class, ['id' => 3]);
        $orderLineItem3 = new OrderLineItem();
        $orderLineItem3->setProductSku('SKU3');
        $subOrder2->setLineItems(new ArrayCollection([$orderLineItem3]));
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusProvider->expects($this->exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatusProvider::AUTHORIZED,
                PaymentStatusProvider::PENDING
            );

        $this->checkoutLineItemsProvider->expects($this->once())
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

        $this->assertFalse($checkout->getLineItems()->isEmpty());

        $skus = $checkout->getLineItems()->map(fn (CheckoutLineItem $lineItem) => $lineItem->getProductSku());
        $this->assertEqualsCanonicalizing(['SKU1', 'SKU2'], $skus->toArray());
    }

    public function testExecuteWhenNotPaidAction()
    {
        $checkoutOption = new PropertyPath('checkout');
        $orderOption = new PropertyPath('order');

        $checkout = new Checkout();
        $lineItem1 = new CheckoutLineItem();
        $lineItem1->setProductSku('SKU1');
        $lineItem2 = new CheckoutLineItem();
        $lineItem2->setProductSku('SKU2');
        $checkout->setLineItems(new ArrayCollection([$lineItem1, $lineItem2]));

        $order = $this->getEntity(Order::class, ['id' => 1]);
        $subOrder1 = $this->getEntity(Order::class, ['id' => 2]);
        $orderLineItem1 = new OrderLineItem();
        $orderLineItem1->setProductSku('SKU1');
        $subOrder1->setLineItems(new ArrayCollection([$orderLineItem1]));
        $subOrder2 = $this->getEntity(Order::class, ['id' => 2]);
        $orderLineItem2 = new OrderLineItem();
        $orderLineItem2->setProductSku('SKU2');
        $subOrder2->setLineItems(new ArrayCollection([$orderLineItem2]));
        $order->addSubOrder($subOrder1);
        $order->addSubOrder($subOrder2);

        $context = new \stdClass();
        $context->checkout = $checkout;
        $context->order = $order;

        $this->contextAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive(
                [$context, $checkoutOption],
                [$context, $orderOption]
            )
            ->willReturnOnConsecutiveCalls(
                $checkout,
                $order
            );

        $this->paymentStatusProvider->expects($this->exactly(2))
            ->method('getPaymentStatus')
            ->withConsecutive(
                [$subOrder1],
                [$subOrder2]
            )
            ->willReturnOnConsecutiveCalls(
                PaymentStatusProvider::AUTHORIZED,
                PaymentStatusProvider::AUTHORIZED
            );

        $this->checkoutLineItemsProvider->expects($this->never())
            ->method('getProductSkusWithDifferences');

        $this->action->initialize([
            'checkout' => $checkoutOption,
            'order' => $orderOption
        ]);
        $this->action->execute($context);

        $this->assertTrue($checkout->getLineItems()->isEmpty());
    }
}
