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
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var OrderShippingLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingLineItemConverter;

    /** @var ShippingContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingContextBuilderFactory;

    /** @var PaymentTransactionRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var PaymentTransaction|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransaction;

    /** @var ShippingContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $contextBuilder;

    /** @var OrderShippingContextFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->shippingLineItemConverter = $this->createMock(OrderShippingLineItemConverterInterface::class);
        $this->shippingContextBuilderFactory = $this->createMock(ShippingContextBuilderFactoryInterface::class);
        $this->repository = $this->createMock(PaymentTransactionRepository::class);
        $this->paymentTransaction = $this->createMock(PaymentTransaction::class);
        $this->contextBuilder = $this->createMock(ShippingContextBuilderInterface::class);

        $this->factory = new OrderShippingContextFactory(
            $this->doctrineHelper,
            $this->shippingLineItemConverter,
            $this->shippingContextBuilderFactory
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

        $this->shippingLineItemConverter->expects($this->once())
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

        $this->contextBuilder->expects($this->once())
            ->method('setPaymentMethod')
            ->with(self::TEST_PAYMENT_METHOD);

        $this->contextBuilder->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItemCollection);

        $this->shippingContextBuilderFactory->expects($this->once())
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

        $this->shippingLineItemConverter->expects($this->once())
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

        $this->contextBuilder->expects($this->once())
            ->method('setPaymentMethod')
            ->with(self::TEST_PAYMENT_METHOD);

        $this->contextBuilder->expects($this->never())
            ->method('setLineItems');

        $this->shippingContextBuilderFactory->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($this->contextBuilder);

        $this->factory->create($order);
    }

    public function testCreateNullBuilderFactory()
    {
        $this->factory = new OrderShippingContextFactory(
            $this->doctrineHelper,
            $this->shippingLineItemConverter
        );
        $this->shippingContextBuilderFactory->expects(self::never())
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
        $this->paymentTransaction->expects(self::once())
            ->method('getPaymentMethod')
            ->willReturn(self::TEST_PAYMENT_METHOD);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->willReturn($this->paymentTransaction);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(PaymentTransaction::class)
            ->willReturn($this->repository);

        return parent::prepareOrder();
    }
}
