<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

class OrderShippingContextFactoryTest extends AbstractOrderContextFactoryTest
{
    /**
     * @var OrderShippingContextFactory
     */
    private $factory;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var OrderShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingLineItemConverterMock;

    /**
     * @var ShippingContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingContextBuilderFactoryMock;

    /**
     * @var PaymentTransactionRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repositoryMock;

    /**
     * @var PaymentTransaction|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentTransactionMock;

    /**
     * @var ShippingContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextBuilder;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingLineItemConverterMock = $this->createMock(OrderShippingLineItemConverterInterface::class);

        $this->shippingContextBuilderFactoryMock = $this->createMock(ShippingContextBuilderFactoryInterface::class);

        $this->repositoryMock = $this
            ->getMockBuilder(PaymentTransactionRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransactionMock = $this->getMockBuilder(PaymentTransaction::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextBuilder = $this->createMock(ShippingContextBuilderInterface::class);

        $this->factory = new OrderShippingContextFactory(
            $this->doctrineHelper,
            $this->shippingLineItemConverterMock,
            $this->shippingContextBuilderFactoryMock
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreate()
    {
        $order = $this->prepareOrder();

        $shippingLineItems = [
            new ShippingLineItem(
                [
                    ShippingLineItem::FIELD_QUANTITY => 10,
                    ShippingLineItem::FIELD_PRICE => Price::create($order->getSubtotal(), $order->getCurrency()),
                ]
            ),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($order->getSubtotal(), $order->getCurrency())),
        ];

        $shippingLineItemCollection = new DoctrineShippingLineItemCollection($shippingLineItems);

        $this->shippingLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn($shippingLineItemCollection);

        $this->prepareContextBuilder(
            $this->contextBuilder,
            $order->getBillingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );

        $this->contextBuilder
            ->expects($this->once())
            ->method('setPaymentMethod')
            ->with(self::TEST_PAYMENT_METHOD);

        $this->contextBuilder
            ->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItemCollection);

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($this->contextBuilder);

        $this->factory->create($order);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testWithNullLineItems()
    {
        $order = $this->prepareOrder();

        $this->shippingLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn(null);

        $this->prepareContextBuilder(
            $this->contextBuilder,
            $order->getBillingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );

        $this->contextBuilder
            ->expects($this->once())
            ->method('setPaymentMethod')
            ->with(self::TEST_PAYMENT_METHOD);

        $this->contextBuilder
            ->expects($this->never())
            ->method('setLineItems');

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($this->contextBuilder);

        $this->factory->create($order);
    }

    public function testCreateNullBuilderFactory()
    {
        $this->factory = new OrderShippingContextFactory(
            $this->doctrineHelper,
            $this->shippingLineItemConverterMock
        );
        $this->shippingContextBuilderFactoryMock
            ->expects(static::never())
            ->method('createShippingContextBuilder');

        $this->assertNull($this->factory->create(new Order()));
    }

    public function testUnsupportedEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->create(new \stdClass());
    }

    protected function prepareOrder()
    {
        $this->paymentTransactionMock
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->willReturn(self::TEST_PAYMENT_METHOD);

        $this->repositoryMock
            ->expects(static::once())
            ->method('findOneBy')
            ->willReturn($this->paymentTransactionMock);

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->repositoryMock);

        return parent::prepareOrder();
    }
}
