<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
use Oro\Bundle\WebsiteBundle\Entity\Website;

class OrderPaymentContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_SHIPPING_METHOD = 'SomeShippingMethod';

    /** @var OrderPaymentLineItemConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentLineItemConverter;

    /** @var PaymentContextBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentContextBuilderFactory;

    /** @var OrderPaymentContextFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->paymentLineItemConverter = $this->createMock(OrderPaymentLineItemConverterInterface::class);
        $this->paymentContextBuilderFactory = $this->createMock(PaymentContextBuilderFactoryInterface::class);

        $this->factory = new OrderPaymentContextFactory(
            $this->paymentLineItemConverter,
            $this->paymentContextBuilderFactory
        );
    }

    private function getContextBuilder(
        AddressInterface $address,
        Price $subtotal,
        string $currency,
        Website $website,
        Customer $customer,
        CustomerUser $customerUser
    ): PaymentContextBuilderInterface|\PHPUnit\Framework\MockObject\MockObject {
        $contextBuilder = $this->createMock(PaymentContextBuilderInterface::class);
        $contextBuilder->expects(self::any())
            ->method('setShippingAddress')
            ->with($address)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setBillingAddress')
            ->with($address)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCustomer')
            ->with($customer)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCustomerUser')
            ->with($customerUser)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setSubTotal')
            ->with($subtotal)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setCurrency')
            ->with($currency)
            ->willReturnSelf();
        $contextBuilder->expects(self::any())
            ->method('setWebsite')
            ->with($website)
            ->willReturnSelf();
        $contextBuilder->expects(self::once())
            ->method('getResult');

        return $contextBuilder;
    }

    private function getOrder(): Order
    {
        $address = $this->createMock(OrderAddress::class);
        $currency = 'USD';
        $amount = 100;
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $websiteMock = $this->createMock(Website::class);

        $orderLineItem1 = new OrderLineItem();
        $orderLineItem1->setQuantity(10);
        $orderLineItem1->setPrice(Price::create($amount, $currency));

        $orderLineItem2 = new OrderLineItem();
        $orderLineItem2->setQuantity(20);
        $orderLineItem2->setPrice(Price::create($amount, $currency));

        $order = new Order();
        $order->setBillingAddress($address);
        $order->setShippingAddress($address);
        $order->setShippingMethod(self::TEST_SHIPPING_METHOD);
        $order->setCurrency($currency);
        $order->setLineItems(new ArrayCollection([$orderLineItem1, $orderLineItem2]));
        $order->setSubtotal($amount);
        $order->setCurrency($currency);
        $order->setCustomer($customer);
        $order->setCustomerUser($customerUser);
        $order->setWebsite($websiteMock);

        return $order;
    }

    public function testCreate()
    {
        $order = $this->getOrder();

        $paymentLineItems = [
            new PaymentLineItem([
                PaymentLineItem::FIELD_QUANTITY => 10,
                PaymentLineItem::FIELD_PRICE => Price::create($order->getSubtotal(), $order->getCurrency()),
            ]),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($order->getSubtotal(), $order->getCurrency())),
        ];

        $paymentLineItemCollection = new DoctrinePaymentLineItemCollection($paymentLineItems);

        $this->paymentLineItemConverter->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn($paymentLineItemCollection);

        $contextBuilder = $this->getContextBuilder(
            $order->getShippingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );
        $contextBuilder->expects($this->once())
            ->method('setLineItems')
            ->with($paymentLineItemCollection);
        $contextBuilder->expects($this->once())
            ->method('setShippingMethod')
            ->with(self::TEST_SHIPPING_METHOD);
        $contextBuilder->expects($this->once())
            ->method('setTotal')
            ->with($order->getTotal());

        $this->paymentContextBuilderFactory->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($contextBuilder);

        $this->factory->create($order);
    }

    public function testWithEmptyLineItems()
    {
        $order = $this->getOrder();

        $this->paymentLineItemConverter->expects($this->once())
            ->method('convertLineItems')
            ->with($order->getLineItems())
            ->willReturn(new DoctrinePaymentLineItemCollection([]));

        $contextBuilder = $this->getContextBuilder(
            $order->getShippingAddress(),
            Price::create($order->getSubtotal(), $order->getCurrency()),
            $order->getCurrency(),
            $order->getWebsite(),
            $order->getCustomer(),
            $order->getCustomerUser()
        );
        $contextBuilder->expects($this->never())
            ->method('setLineItems');
        $contextBuilder->expects($this->once())
            ->method('setShippingMethod')
            ->with(self::TEST_SHIPPING_METHOD);

        $this->paymentContextBuilderFactory->expects($this->once())
            ->method('createPaymentContextBuilder')
            ->with($order, (string)$order->getId())
            ->willReturn($contextBuilder);

        $this->factory->create($order);
    }
}
