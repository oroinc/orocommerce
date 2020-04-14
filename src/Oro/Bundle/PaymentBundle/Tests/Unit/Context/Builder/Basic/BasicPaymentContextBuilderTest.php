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
    /**
     * @var Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerMock;

    /**
     * @var CustomerUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerUserMock;

    /**
     * @var PaymentLineItemCollectionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lineItemsCollectionMock;

    /**
     * @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $billingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingAddressMock;

    /**
     * @var Price|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subtotalMock;

    /**
     * @var Checkout|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sourceEntityMock;

    /**
     * @var PaymentLineItemCollectionFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentLineItemCollectionFactoryMock;

    /**
     * @var Website|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteMock;

    /**
     * @var ShippingOrigin|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingOriginMock;

    protected function setUp(): void
    {
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerUserMock = $this->getMockBuilder(CustomerUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->lineItemsCollectionMock = $this->createMock(PaymentLineItemCollectionInterface::class);
        $this->billingAddressMock = $this->createMock(AddressInterface::class);
        $this->shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceEntityMock = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentLineItemCollectionFactoryMock = $this->createMock(
            PaymentLineItemCollectionFactoryInterface::class
        );
        $this->websiteMock = $this->createMock(Website::class);
        $this->shippingOriginMock = $this->createMock(ShippingOrigin::class);
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

        $this->lineItemsCollectionMock
            ->expects(static::once())
            ->method('toArray')
            ->willReturn($lineItems);

        $this->paymentLineItemCollectionFactoryMock
            ->expects(static::once())
            ->method('createPaymentLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollectionMock);

        $builder = new BasicPaymentContextBuilder(
            $this->sourceEntityMock,
            $entityId,
            $this->paymentLineItemCollectionFactoryMock
        );

        $totalAmount = 10.0;
        $builder
            ->setCurrency($currency)
            ->setSubTotal($this->subtotalMock)
            ->setLineItems($this->lineItemsCollectionMock)
            ->setShippingAddress($this->shippingAddressMock)
            ->setBillingAddress($this->billingAddressMock)
            ->setCustomer($this->customerMock)
            ->setCustomerUser($this->customerUserMock)
            ->setShippingMethod($shippingMethod)
            ->setWebsite($this->websiteMock)
            ->setShippingOrigin($this->shippingOriginMock)
            ->setTotal($totalAmount);

        $expectedContext = $this->getExpectedFullContext(
            $shippingMethod,
            $currency,
            $entityId,
            $totalAmount
        );
        $context = $builder->getResult();

        static::assertEquals($expectedContext, $context);
    }

    public function testOptionalFields()
    {
        $entityId = '12';
        $lineItems = [];

        $this->paymentLineItemCollectionFactoryMock
            ->expects(static::once())
            ->method('createPaymentLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollectionMock);

        $builder = new BasicPaymentContextBuilder(
            $this->sourceEntityMock,
            $entityId,
            $this->paymentLineItemCollectionFactoryMock
        );

        $expectedContext = $this->getExpectedContextWithoutOptionalFields($entityId);

        $context = $builder->getResult();

        static::assertEquals($expectedContext, $context);
    }

    /**
     * @param $shippingMethod
     * @param $currency
     * @param $entityId
     * @param float $total
     *
     * @return PaymentContext
     */
    private function getExpectedFullContext($shippingMethod, $currency, $entityId, $total)
    {
        $params = [
            PaymentContext::FIELD_CUSTOMER => $this->customerMock,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUserMock,
            PaymentContext::FIELD_LINE_ITEMS => $this->lineItemsCollectionMock,
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddressMock,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddressMock,
            PaymentContext::FIELD_SHIPPING_METHOD => $shippingMethod,
            PaymentContext::FIELD_CURRENCY => $currency,
            PaymentContext::FIELD_SUBTOTAL => $this->subtotalMock,
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntityMock,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
            PaymentContext::FIELD_WEBSITE => $this->websiteMock,
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOriginMock,
            PaymentContext::FIELD_TOTAL => $total
        ];

        return new PaymentContext($params);
    }

    /**
     * @param $entityId
     *
     * @return PaymentContext
     */
    private function getExpectedContextWithoutOptionalFields($entityId)
    {
        $params = [
            PaymentContext::FIELD_LINE_ITEMS => $this->lineItemsCollectionMock,
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntityMock,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $entityId,
        ];

        return new PaymentContext($params);
    }
}
