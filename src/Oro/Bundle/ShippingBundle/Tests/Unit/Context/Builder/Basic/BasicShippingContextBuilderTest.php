<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicShippingContextBuilderTest extends \PHPUnit\Framework\TestCase
{
    public function testFullContextBuilding(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $entityId = '12';
        $lineItemsCollection = $this->createMock(ShippingLineItemCollectionInterface::class);
        $billingAddress = $this->createMock(AddressInterface::class);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $shippingOrigin = $this->createMock(ShippingOrigin::class);
        $paymentMethod = 'paymentMethod';
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $subtotal = $this->createMock(Price::class);
        $currency = 'usd';
        $website = $this->createMock(Website::class);

        $builder = new BasicShippingContextBuilder($entity, $entityId);
        $builder
            ->setLineItems($lineItemsCollection)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShippingOrigin($shippingOrigin)
            ->setPaymentMethod($paymentMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setSubTotal($subtotal)
            ->setCurrency($currency)
            ->setWebsite($website);

        $this->assertEquals(
            new ShippingContext([
                ShippingContext::FIELD_SOURCE_ENTITY => $entity,
                ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
                ShippingContext::FIELD_LINE_ITEMS => $lineItemsCollection,
                ShippingContext::FIELD_BILLING_ADDRESS => $billingAddress,
                ShippingContext::FIELD_SHIPPING_ADDRESS => $shippingAddress,
                ShippingContext::FIELD_SHIPPING_ORIGIN => $shippingOrigin,
                ShippingContext::FIELD_PAYMENT_METHOD => $paymentMethod,
                ShippingContext::FIELD_CUSTOMER => $customer,
                ShippingContext::FIELD_CUSTOMER_USER => $customerUser,
                ShippingContext::FIELD_SUBTOTAL => $subtotal,
                ShippingContext::FIELD_CURRENCY => $currency,
                ShippingContext::FIELD_WEBSITE => $website,
            ]),
            $builder->getResult()
        );
    }

    public function testOptionalFields(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $entityId = '12';

        $builder = new BasicShippingContextBuilder($entity, $entityId);

        $this->assertEquals(
            new ShippingContext([
                ShippingContext::FIELD_SOURCE_ENTITY => $entity,
                ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
                ShippingContext::FIELD_LINE_ITEMS => null,
            ]),
            $builder->getResult()
        );
    }
}
