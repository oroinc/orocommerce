<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentContextTest extends TestCase
{
    /** @var Customer|MockObject */
    private $customer;

    /** @var CustomerUser|MockObject */
    private $customerUser;

    /** @var Collection<PaymentLineItem>|MockObject */
    private $lineItemsCollection;

    /** @var AddressInterface|MockObject */
    private $billingAddress;

    /** @var AddressInterface|MockObject */
    private $shippingAddress;

    /** @var Price|MockObject */
    private $subtotal;

    /** @var object|MockObject */
    private $sourceEntity;

    /** @var Website|MockObject */
    private $website;

    #[\Override]
    protected function setUp(): void
    {
        $this->customer = $this->createMock(Customer::class);
        $this->customerUser = $this->createMock(CustomerUser::class);
        $this->lineItemsCollection = new ArrayCollection([]);
        $this->billingAddress = $this->createMock(AddressInterface::class);
        $this->shippingAddress = $this->createMock(AddressInterface::class);
        $this->subtotal = $this->createMock(Price::class);
        $this->sourceEntity = $this->createMock(\stdClass::class);
        $this->website = $this->createMock(Website::class);
    }

    public function testConstructionAndGetters(): void
    {
        $shippingMethod = 'shippingMethod';
        $currency = 'usd';
        $entityId = '12';
        $totalAmount = 10.0;

        $params = [
            PaymentContext::FIELD_CUSTOMER => $this->customer,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUser,
            PaymentContext::FIELD_LINE_ITEMS => $this->lineItemsCollection,
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddress,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddress,
            PaymentContext::FIELD_SHIPPING_METHOD => $shippingMethod,
            PaymentContext::FIELD_CURRENCY => $currency,
            PaymentContext::FIELD_SUBTOTAL => $this->subtotal,
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
            PaymentContext::FIELD_WEBSITE => $this->website,
            PaymentContext::FIELD_TOTAL => $totalAmount
        ];

        $paymentContext = new PaymentContext($params);

        $getterValues = [
            PaymentContext::FIELD_CUSTOMER => $paymentContext->getCustomer(),
            PaymentContext::FIELD_CUSTOMER_USER => $paymentContext->getCustomerUser(),
            PaymentContext::FIELD_LINE_ITEMS => $paymentContext->getLineItems(),
            PaymentContext::FIELD_BILLING_ADDRESS => $paymentContext->getBillingAddress(),
            PaymentContext::FIELD_SHIPPING_ADDRESS => $paymentContext->getShippingAddress(),
            PaymentContext::FIELD_SHIPPING_METHOD => $paymentContext->getShippingMethod(),
            PaymentContext::FIELD_CURRENCY => $paymentContext->getCurrency(),
            PaymentContext::FIELD_SUBTOTAL => $paymentContext->getSubtotal(),
            PaymentContext::FIELD_SOURCE_ENTITY => $paymentContext->getSourceEntity(),
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $paymentContext->getSourceEntityIdentifier(),
            PaymentContext::FIELD_WEBSITE => $paymentContext->getWebsite(),
            PaymentContext::FIELD_TOTAL => $paymentContext->getTotal()
        ];

        self::assertEquals($params, $getterValues);
    }
}
