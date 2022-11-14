<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Builder\Basic;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
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
    /** @var Customer|\PHPUnit\Framework\MockObject\MockObject */
    private $customer;

    /** @var CustomerUser|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUser;

    /** @var PaymentLineItemCollectionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemsCollection;

    /** @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $billingAddress;

    /** @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingAddress;

    /** @var Price|\PHPUnit\Framework\MockObject\MockObject */
    private $subtotal;

    /** @var Checkout|\PHPUnit\Framework\MockObject\MockObject */
    private $sourceEntity;

    /** @var PaymentLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentLineItemCollectionFactory;

    /** @var Website|\PHPUnit\Framework\MockObject\MockObject */
    private $website;

    /** @var ShippingOrigin|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOrigin;

    protected function setUp(): void
    {
        $this->customer = $this->createMock(Customer::class);
        $this->customerUser = $this->createMock(CustomerUser::class);
        $this->lineItemsCollection = $this->createMock(PaymentLineItemCollectionInterface::class);
        $this->billingAddress = $this->createMock(AddressInterface::class);
        $this->shippingAddress = $this->createMock(AddressInterface::class);
        $this->subtotal = $this->createMock(Price::class);
        $this->sourceEntity = $this->createMock(Checkout::class);
        $this->paymentLineItemCollectionFactory = $this->createMock(PaymentLineItemCollectionFactoryInterface::class);
        $this->website = $this->createMock(Website::class);
        $this->shippingOrigin = $this->createMock(ShippingOrigin::class);
    }

    public function testFullContextBuilding()
    {
        $shippingMethod = 'shippingMethod';
        $currency = 'usd';
        $entityId = '12';
        $lineItems = [
            new PaymentLineItem([PaymentLineItem::FIELD_QUANTITY => 2]),
            new PaymentLineItem([PaymentLineItem::FIELD_QUANTITY => 5])
        ];

        $this->lineItemsCollection->expects(self::once())
            ->method('toArray')
            ->willReturn($lineItems);

        $this->paymentLineItemCollectionFactory->expects(self::once())
            ->method('createPaymentLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollection);

        $builder = new BasicPaymentContextBuilder(
            $this->sourceEntity,
            $entityId,
            $this->paymentLineItemCollectionFactory
        );

        $totalAmount = 10.0;
        $builder
            ->setCurrency($currency)
            ->setSubTotal($this->subtotal)
            ->setLineItems($this->lineItemsCollection)
            ->setShippingAddress($this->shippingAddress)
            ->setBillingAddress($this->billingAddress)
            ->setCustomer($this->customer)
            ->setCustomerUser($this->customerUser)
            ->setShippingMethod($shippingMethod)
            ->setWebsite($this->website)
            ->setShippingOrigin($this->shippingOrigin)
            ->setTotal($totalAmount);

        $expectedContext = $this->getExpectedFullContext(
            $shippingMethod,
            $currency,
            $entityId,
            $totalAmount
        );
        $context = $builder->getResult();

        self::assertEquals($expectedContext, $context);
    }

    public function testOptionalFields()
    {
        $entityId = '12';
        $lineItems = [];

        $this->paymentLineItemCollectionFactory->expects(self::once())
            ->method('createPaymentLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollection);

        $builder = new BasicPaymentContextBuilder(
            $this->sourceEntity,
            $entityId,
            $this->paymentLineItemCollectionFactory
        );

        $expectedContext = $this->getExpectedContextWithoutOptionalFields($entityId);

        $context = $builder->getResult();

        self::assertEquals($expectedContext, $context);
    }

    private function getExpectedFullContext(
        string $shippingMethod,
        string $currency,
        int $entityId,
        float $total
    ): PaymentContext {
        return new PaymentContext([
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
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOrigin,
            PaymentContext::FIELD_TOTAL => $total
        ]);
    }

    private function getExpectedContextWithoutOptionalFields(int $entityId): PaymentContext
    {
        return new PaymentContext([
            PaymentContext::FIELD_LINE_ITEMS => $this->lineItemsCollection,
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
        ]);
    }
}
