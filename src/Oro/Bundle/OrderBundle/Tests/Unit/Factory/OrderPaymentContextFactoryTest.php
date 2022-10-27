<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;

class OrderPaymentContextFactoryTest extends AbstractOrderContextFactoryTest
{
    /**
     * @var OrderPaymentContextFactory
     */
    private $factory;

    /**
     * @var OrderPaymentLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentLineItemConverterMock;

    /**
     * @var PaymentContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentContextBuilderFactoryMock;

    /**
     * @var PaymentContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextBuilder;

    protected function setUp(): void
    {
        $this->paymentLineItemConverterMock = $this->createMock(OrderPaymentLineItemConverterInterface::class);

        $this->paymentContextBuilderFactoryMock = $this->createMock(PaymentContextBuilderFactoryInterface::class);

        $this->contextBuilder = $this->createMock(PaymentContextBuilderInterface::class);

        $this->factory = new OrderPaymentContextFactory(
            $this->paymentLineItemConverterMock,
            $this->paymentContextBuilderFactoryMock
        );
    }

    public function testCreate()
    {
        $order = $this->prepareOrder();

        $paymentLineItems = [
            new PaymentLineItem(
                [
                    PaymentLineItem::FIELD_QUANTITY => 10,
                    PaymentLineItem::FIELD_PRICE => Price::create($order->getSubtotal(), $order->getCurrency()),
                ]
            ),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($order->getSubtotal(), $order->getCurrency())),
        ];

        $paymentLineItemCollection = new DoctrinePaymentLineItemCollection($paymentLineItems);

        $this->paymentLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn($paymentLineItemCollection);

        $this->prepareContextBuilder(
            $this->contextBuilder,
            $order->getShippingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );

        $this->contextBuilder
            ->expects($this->once())
            ->method('setLineItems')
            ->with($paymentLineItemCollection);

        $this->contextBuilder
            ->expects($this->once())
            ->method('setShippingMethod')
            ->with(self::TEST_SHIPPING_METHOD);

        $this->contextBuilder
            ->expects($this->once())
            ->method('setTotal')
            ->with($order->getTotal());

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($this->contextBuilder);

        $this->factory->create($order);
    }

    public function testWithNullLineItems()
    {
        $order = $this->prepareOrder();

        $this->paymentLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn(null);

        $this->prepareContextBuilder(
            $this->contextBuilder,
            $order->getShippingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );

        $this->contextBuilder
            ->expects($this->never())
            ->method('setLineItems');

        $this->contextBuilder
            ->expects($this->once())
            ->method('setShippingMethod')
            ->with(self::TEST_SHIPPING_METHOD);

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($this->contextBuilder);

        $this->factory->create($order);
    }

    public function testCreateNullBuilderFactory()
    {
        $this->factory = new OrderPaymentContextFactory(
            $this->paymentLineItemConverterMock
        );
        $this->paymentContextBuilderFactoryMock
            ->expects(static::never())
            ->method('createPaymentContextBuilder');

        $this->assertNull($this->factory->create(new Order()));
    }
}
