<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\Basic\BasicPaymentContextBuilder;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BasicPaymentContextBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentLineItemCollectionFactory;

    protected function setUp(): void
    {
        $this->paymentLineItemCollectionFactory = $this->createMock(PaymentLineItemCollectionFactoryInterface::class);
    }

    public function testFullContextBuilding()
    {
        $entity = $this->createMock(\stdClass::class);
        $entityId = '12';
        $initialLineItems = [
            new PaymentLineItem([PaymentLineItem::FIELD_QUANTITY => 2]),
            new PaymentLineItem([PaymentLineItem::FIELD_QUANTITY => 5])
        ];
        $initialLineItemsCollection = $this->createMock(PaymentLineItemCollectionInterface::class);
        $additionalLineItem = new PaymentLineItem([PaymentLineItem::FIELD_QUANTITY => 10]);
        $lineItems = $initialLineItems;
        $lineItems[] = $additionalLineItem;
        $lineItemsCollection = $this->createMock(PaymentLineItemCollectionInterface::class);
        $billingAddress = $this->createMock(AddressInterface::class);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $shippingOrigin = $this->createMock(ShippingOrigin::class);
        $shippingMethod = 'shippingMethod';
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $subtotal = $this->createMock(Price::class);
        $currency = 'usd';
        $website = $this->createMock(Website::class);
        $total = 10.0;

        $initialLineItemsCollection->expects(self::once())
            ->method('toArray')
            ->willReturn($initialLineItems);

        $this->paymentLineItemCollectionFactory->expects(self::once())
            ->method('createPaymentLineItemCollection')
            ->with($lineItems)
            ->willReturn($lineItemsCollection);

        $builder = new BasicPaymentContextBuilder($entity, $entityId, $this->paymentLineItemCollectionFactory);
        $builder
            ->setLineItems($initialLineItemsCollection)
            ->addLineItem($additionalLineItem)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShippingOrigin($shippingOrigin)
            ->setShippingMethod($shippingMethod)
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setSubTotal($subtotal)
            ->setCurrency($currency)
            ->setWebsite($website)
            ->setTotal($total);

        self::assertEquals(
            new PaymentContext([
                PaymentContext::FIELD_SOURCE_ENTITY => $entity,
                PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
                PaymentContext::FIELD_LINE_ITEMS => $lineItemsCollection,
                PaymentContext::FIELD_BILLING_ADDRESS => $billingAddress,
                PaymentContext::FIELD_SHIPPING_ADDRESS => $shippingAddress,
                PaymentContext::FIELD_SHIPPING_ORIGIN => $shippingOrigin,
                PaymentContext::FIELD_SHIPPING_METHOD => $shippingMethod,
                PaymentContext::FIELD_CUSTOMER => $customer,
                PaymentContext::FIELD_CUSTOMER_USER => $customerUser,
                PaymentContext::FIELD_SUBTOTAL => $subtotal,
                PaymentContext::FIELD_CURRENCY => $currency,
                PaymentContext::FIELD_WEBSITE => $website,
                PaymentContext::FIELD_TOTAL => $total,
            ]),
            $builder->getResult()
        );
    }

    public function testOptionalFields(): void
    {
        $entity = $this->createMock(\stdClass::class);
        $entityId = '12';
        $lineItemsCollection = $this->createMock(PaymentLineItemCollectionInterface::class);

        $this->paymentLineItemCollectionFactory->expects(self::once())
            ->method('createPaymentLineItemCollection')
            ->with([])
            ->willReturn($lineItemsCollection);

        $builder = new BasicPaymentContextBuilder($entity, $entityId, $this->paymentLineItemCollectionFactory);

        self::assertEquals(
            new PaymentContext([
                PaymentContext::FIELD_SOURCE_ENTITY => $entity,
                PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
                PaymentContext::FIELD_LINE_ITEMS => $lineItemsCollection,
            ]),
            $builder->getResult()
        );
    }
}
