<?php

namespace Oro\Bundle\OrderBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Converter\OrderShippingLineItemConverterInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
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
    const TEST_PAYMENT_METHOD = 'SomePaymentMethod';

    /**
     * @var OrderShippingContextFactory
     */
    private $factory;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /**
     * @var OrderShippingLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingLineItemConverterMock;

    /**
     * @var ShippingContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextBuilderFactoryMock;

    /**
     * @var PaymentTransactionRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repositoryMock;

    /**
     * @var PaymentTransaction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentTransactionMock;

    /**
     * @var ShippingContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextBuilder;

    protected function setUp()
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
            ->with(
                $order->getCurrency(),
                Price::create($order->getSubtotal(), $order->getCurrency()),
                $order,
                (string)$order->getId()
            )
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
            ->with(
                $order->getCurrency(),
                Price::create($order->getSubtotal(), $order->getCurrency()),
                $order,
                (string)$order->getId()
            )
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUnsupportedEntity()
    {
        $this->factory->create(new \stdClass());
    }

    /**
     * @return Order
     */
    protected function prepareOrder()
    {
        /** @var AddressInterface $address */
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $amount = 100;
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);

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

        $ordersLineItems = [
            (new OrderLineItem())
                ->setQuantity(10)
                ->setPrice(Price::create($amount, $currency)),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($amount, $currency))
        ];

        $orderLineItemsCollection = new ArrayCollection($ordersLineItems);

        $order = (new Order())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setLineItems($orderLineItemsCollection)
            ->setSubtotal($amount)
            ->setCurrency($currency)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser);

        return $order;
    }
}
