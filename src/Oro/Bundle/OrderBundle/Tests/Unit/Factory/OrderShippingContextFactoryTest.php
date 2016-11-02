<?php

namespace Oro\Bundle\OrderBundle\Bundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Factory\ShippingContextFactory;

class OrderShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $factory;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var  ShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $shippingContextFactory;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextFactory = $this->getMockBuilder(ShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->factory = new OrderShippingContextFactory(
            $this->doctrineHelper,
            $this->shippingContextFactory
        );
    }

    public function testCreate()
    {
        /** @var AddressInterface $address */
        $address = $this->getMock(OrderAddress::class);
        $currency = 'USD';
        $paymentMethod = 'SomePaymentMethod';
        $amount = 100;

        $paymentTransMock = $this->getMock(PaymentTransaction::class);
        $paymentTransMock->expects(static::once())->method('getPaymentMethod')->willReturn($paymentMethod);

        $repoMock = $this->getMockBuilder(PaymentTransactionRepository::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects(static::once())->method('getEntityRepository')->with(PaymentTransaction::class)
            ->willReturn($repoMock);
        $repoMock->expects(static::once())->method('findOneBy')
            ->willReturn($paymentTransMock);

        $lineItems = new ArrayCollection();
        $lineItems->add(
            (new OrderLineItem())
            ->setQuantity(10)
            ->setPrice(Price::create($amount, $currency))
        );
        $lineItems->add(
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($amount, $currency))
        );

        $order = (new Order())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setCurrency($currency)
            ->setLineItems($lineItems)
            ->setSubtotal($amount)
            ->setCurrency($currency);

        $context = new ShippingContext();
        $context->setBillingAddress($address);
        $context->setShippingAddress($address);
        $context->setCurrency($currency);
        $context->setLineItems([
            (new ShippingLineItem())->setQuantity(10)->setPrice(Price::create($amount, $currency)),
            (new ShippingLineItem())->setQuantity(20)->setPrice(Price::create($amount, $currency)),
        ]);
        $context->setPaymentMethod($paymentMethod);
        $context->setSubtotal(Price::create($amount, $currency));

        $this->shippingContextFactory
            ->expects(static::once())
            ->method('create')
            ->willReturn(new ShippingContext());

        static::assertEquals($context, $this->factory->create($order));
    }
}
