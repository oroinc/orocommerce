<?php

namespace Oro\Bundle\OrderBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderPaymentLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Factory\OrderPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\Builder\Factory\PaymentContextBuilderFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;

class OrderPaymentContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderPaymentContextFactory
     */
    private $factory;

    /**
     * @var OrderPaymentLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentLineItemConverterMock;

    /**
     * @var PaymentContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextBuilderFactoryMock;

    /**
     * @var PaymentContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextBuilder;

    protected function setUp()
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
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $shippingMethod = 'SomeShippingMethod';
        $amount = 100;

        $ordersLineItems = [
            (new OrderLineItem())
                ->setQuantity(10)
                ->setPrice(Price::create($amount, $currency)),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($amount, $currency)),
        ];

        $orderLineItemsCollection = new ArrayCollection($ordersLineItems);

        $paymentLineItems = [
            new PaymentLineItem(
                [
                    PaymentLineItem::FIELD_QUANTITY => 10,
                    PaymentLineItem::FIELD_PRICE => Price::create($amount, $currency),
                ]
            ),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($amount, $currency)),
        ];

        $paymentLineItemCollection = new DoctrinePaymentLineItemCollection($paymentLineItems);

        $this->paymentLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($orderLineItemsCollection)
            ->willReturn($paymentLineItemCollection);

        $order = (new Order())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setShippingMethod($shippingMethod)
            ->setCurrency($currency)
            ->setLineItems($orderLineItemsCollection)
            ->setSubtotal($amount)
            ->setCurrency($currency);

        $this->contextBuilder
            ->method('setShippingAddress')
            ->with($address);

        $this->contextBuilder
            ->method('setBillingAddress')
            ->with($address);

        $this->contextBuilder
            ->expects($this->once())
            ->method('setLineItems')
            ->with($paymentLineItemCollection);

        $this->contextBuilder
            ->expects($this->once())
            ->method('setShippingMethod')
            ->with($shippingMethod);

        $this->contextBuilder
            ->expects($this->once())
            ->method('getResult');

        $this->paymentContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($currency, Price::create($amount, $currency), $order, (string)$order->getId())
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
