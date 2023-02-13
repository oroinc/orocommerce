<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ShippingContextTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructionAndGetters()
    {
        $paymentMethod = 'paymentMethod';
        $currency = 'usd';
        $entityId = '12';

        $params = [
            ShippingContext::FIELD_CUSTOMER => $this->createMock(Customer::class),
            ShippingContext::FIELD_CUSTOMER_USER => $this->createMock(CustomerUser::class),
            ShippingContext::FIELD_LINE_ITEMS => $this->createMock(ShippingLineItemCollectionInterface::class),
            ShippingContext::FIELD_BILLING_ADDRESS => $this->createMock(AddressInterface::class),
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createMock(AddressInterface::class),
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createMock(AddressInterface::class),
            ShippingContext::FIELD_PAYMENT_METHOD => $paymentMethod,
            ShippingContext::FIELD_CURRENCY => $currency,
            ShippingContext::FIELD_SUBTOTAL => $this->createMock(Price::class),
            ShippingContext::FIELD_SOURCE_ENTITY => $this->createMock(\stdClass::class),
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
            ShippingContext::FIELD_WEBSITE => $this->createMock(Website::class),
        ];

        $shippingContext = new ShippingContext($params);

        $getterValues = [
            ShippingContext::FIELD_CUSTOMER => $shippingContext->getCustomer(),
            ShippingContext::FIELD_CUSTOMER_USER => $shippingContext->getCustomerUser(),
            ShippingContext::FIELD_LINE_ITEMS => $shippingContext->getLineItems(),
            ShippingContext::FIELD_BILLING_ADDRESS => $shippingContext->getBillingAddress(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => $shippingContext->getShippingAddress(),
            ShippingContext::FIELD_SHIPPING_ORIGIN => $shippingContext->getShippingOrigin(),
            ShippingContext::FIELD_PAYMENT_METHOD => $shippingContext->getPaymentMethod(),
            ShippingContext::FIELD_CURRENCY => $shippingContext->getCurrency(),
            ShippingContext::FIELD_SUBTOTAL => $shippingContext->getSubtotal(),
            ShippingContext::FIELD_SOURCE_ENTITY => $shippingContext->getSourceEntity(),
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $shippingContext->getSourceEntityIdentifier(),
            ShippingContext::FIELD_WEBSITE => $shippingContext->getWebsite(),
        ];

        $this->assertEquals($params, $getterValues);
    }
}
