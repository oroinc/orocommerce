<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractOrderContextFactoryTest extends \PHPUnit\Framework\TestCase
{
    const TEST_PAYMENT_METHOD = 'SomePaymentMethod';
    const TEST_SHIPPING_METHOD = 'SomeShippingMethod';

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $builder
     * @param AddressInterface                         $address
     * @param string                                   $subtotal
     * @param string                                   $currency
     * @param string                                   $website
     * @param string                                   $customer
     * @param string                                   $customerUser
     */
    protected function prepareContextBuilder(
        \PHPUnit\Framework\MockObject\MockObject $builder,
        AddressInterface $address,
        $subtotal,
        $currency,
        $website,
        $customer,
        $customerUser
    ) {
        $builder->method('setShippingAddress')->with($address)->willReturnSelf();
        $builder->method('setBillingAddress')->with($address)->willReturnSelf();
        $builder->method('setCustomer')->with($customer)->willReturnSelf();
        $builder->method('setCustomerUser')->with($customerUser)->willReturnSelf();
        $builder->method('setSubTotal')->with($subtotal)->willReturnSelf();
        $builder->method('setCurrency')->with($currency)->willReturnSelf();
        $builder->method('setWebsite')->with($website)->willReturnSelf();
        $builder->expects($this->once())->method('getResult');
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
        $websiteMock = $this->createMock(Website::class);

        $ordersLineItems = [
            (new OrderLineItem())
                ->setQuantity(10)
                ->setPrice(Price::create($amount, $currency)),
            (new OrderLineItem())
                ->setQuantity(20)
                ->setPrice(Price::create($amount, $currency)),
        ];

        $orderLineItemsCollection = new ArrayCollection($ordersLineItems);

        $order = (new Order())
            ->setBillingAddress($address)
            ->setShippingAddress($address)
            ->setShippingMethod(self::TEST_SHIPPING_METHOD)
            ->setCurrency($currency)
            ->setLineItems($orderLineItemsCollection)
            ->setSubtotal($amount)
            ->setCurrency($currency)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setWebsite($websiteMock);

        return $order;
    }
}
