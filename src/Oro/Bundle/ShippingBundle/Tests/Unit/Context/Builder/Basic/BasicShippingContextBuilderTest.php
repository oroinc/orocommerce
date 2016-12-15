<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\Builder\Basic;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Basic\BasicShippingContextBuilder;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class BasicShippingContextBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Account|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;

    /**
     * @var AccountUser|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerUserMock;

    /**
     * @var ShippingLineItemCollectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsCollectionMock;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingOriginMock;

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subtotalMock;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceEntityMock;

    /**
     * @var ShippingLineItemCollectionFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingLineItemCollectionFactoryMock;

    /**
     * @var ShippingOriginProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingOriginProviderMock;

    /**
     * @var ShippingOrigin|\PHPUnit_Framework_MockObject_MockObject
     */
    private $defaultShippingOriginMock;

    protected function setUp()
    {
        $this->customerMock = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerUserMock = $this->getMockBuilder(AccountUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->lineItemsCollectionMock = $this->getMock(ShippingLineItemCollectionInterface::class);
        $this->billingAddressMock = $this->getMock(AddressInterface::class);
        $this->shippingAddressMock = $this->getMock(AddressInterface::class);
        $this->shippingOriginMock = $this->getMock(AddressInterface::class);
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceEntityMock = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->shippingLineItemCollectionFactoryMock = $this->getMock(
            ShippingLineItemCollectionFactoryInterface::class
        );
        $this->shippingOriginProviderMock = $this->getMockBuilder(ShippingOriginProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->defaultShippingOriginMock = $this->getMockBuilder(ShippingOrigin::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testFullContextBuilding()
    {
        $paymentMethod = 'paymentMethod';
        $currency = 'usd';
        $entityId = '12';
        $lineItems = [(new ShippingLineItem())->setQuantity(2), (new ShippingLineItem())->setQuantity(5)];

        $this->lineItemsCollectionMock
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($lineItems);

        $this->shippingLineItemCollectionFactoryMock
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollectionMock);

        $this->shippingOriginProviderMock
            ->expects($this->never())
            ->method('getSystemShippingOrigin');

        $builder = new BasicShippingContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId,
            $this->shippingLineItemCollectionFactoryMock,
            $this->shippingOriginProviderMock
        );

        $builder
            ->setLineItems($this->lineItemsCollectionMock)
            ->setShippingAddress($this->shippingAddressMock)
            ->setBillingAddress($this->billingAddressMock)
            ->setCustomer($this->customerMock)
            ->setCustomerUser($this->customerUserMock)
            ->setPaymentMethod($paymentMethod)
            ->setShippingOrigin($this->shippingOriginMock);

        $expectedContext = $this->getExpectedFullContext(
            $paymentMethod,
            $currency,
            $entityId,
            $this->shippingOriginMock
        );
        $context = $builder->getResult();

        $this->assertEquals($expectedContext, $context);
    }

    public function testOptionalFields()
    {
        $currency = 'usd';
        $entityId = '12';
        $lineItems = [];

        $this->shippingLineItemCollectionFactoryMock
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollectionMock);

        $this->shippingOriginProviderMock
            ->expects($this->once())
            ->method('getSystemShippingOrigin')
            ->willReturn($this->shippingOriginMock);

        $builder = new BasicShippingContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId,
            $this->shippingLineItemCollectionFactoryMock,
            $this->shippingOriginProviderMock
        );

        $expectedContext = $this->getExpectedContextWithoutOptionalFields(
            $currency,
            $entityId,
            $this->shippingOriginMock
        );

        $context = $builder->getResult();

        $this->assertEquals($expectedContext, $context);
    }

    public function testWithoutOrigin()
    {
        $paymentMethod = 'paymentMethod';
        $currency = 'usd';
        $entityId = '12';
        $lineItems = [(new ShippingLineItem())->setQuantity(2), (new ShippingLineItem())->setQuantity(5)];
        $street = 'someStreet';

        $this->lineItemsCollectionMock
            ->expects($this->once())
            ->method('toArray')
            ->willReturn($lineItems);

        $this->shippingLineItemCollectionFactoryMock
            ->expects($this->once())
            ->method('createShippingLineItemCollection')
            ->with($lineItems)
            ->willReturn($this->lineItemsCollectionMock);

        $this->defaultShippingOriginMock
            ->method('getStreet')
            ->willReturn($street);

        $this->shippingOriginProviderMock
            ->expects($this->once())
            ->method('getSystemShippingOrigin')
            ->willReturn($this->defaultShippingOriginMock);

        $builder = new BasicShippingContextBuilder(
            $currency,
            $this->subtotalMock,
            $this->sourceEntityMock,
            $entityId,
            $this->shippingLineItemCollectionFactoryMock,
            $this->shippingOriginProviderMock
        );

        $builder
            ->setLineItems($this->lineItemsCollectionMock)
            ->setShippingAddress($this->shippingAddressMock)
            ->setBillingAddress($this->billingAddressMock)
            ->setCustomer($this->customerMock)
            ->setCustomerUser($this->customerUserMock)
            ->setPaymentMethod($paymentMethod);

        $expectedContext = $this->getExpectedFullContext(
            $paymentMethod,
            $currency,
            $entityId,
            $this->defaultShippingOriginMock
        );
        $context = $builder->getResult();

        $this->assertEquals($expectedContext, $context);
        $this->assertEquals($street, $context->getShippingOrigin()->getStreet());
    }

    /**
     * @param $paymentMethod
     * @param $currency
     * @param $entityId
     * @param $shippingOrigin
     *
     * @return ShippingContext
     */
    private function getExpectedFullContext($paymentMethod, $currency, $entityId, $shippingOrigin)
    {
        $params = [
            ShippingContext::FIELD_CUSTOMER => $this->customerMock,
            ShippingContext::FIELD_CUSTOMER_USER => $this->customerUserMock,
            ShippingContext::FIELD_LINE_ITEMS => $this->lineItemsCollectionMock,
            ShippingContext::FIELD_BILLING_ADDRESS => $this->billingAddressMock,
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddressMock,
            ShippingContext::FIELD_SHIPPING_ORIGIN => $shippingOrigin,
            ShippingContext::FIELD_PAYMENT_METHOD => $paymentMethod,
            ShippingContext::FIELD_CURRENCY => $currency,
            ShippingContext::FIELD_SUBTOTAL => $this->subtotalMock,
            ShippingContext::FIELD_SOURCE_ENTITY => $this->sourceEntityMock,
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
        ];

        return new ShippingContext($params);
    }

    /**
     * @param $currency
     * @param $entityId
     * @param $shippingOrigin
     *
     * @return ShippingContext
     */
    private function getExpectedContextWithoutOptionalFields($currency, $entityId, $shippingOrigin)
    {
        $params = [
            ShippingContext::FIELD_LINE_ITEMS => $this->lineItemsCollectionMock,
            ShippingContext::FIELD_SHIPPING_ORIGIN => $shippingOrigin,
            ShippingContext::FIELD_CURRENCY => $currency,
            ShippingContext::FIELD_SUBTOTAL => $this->subtotalMock,
            ShippingContext::FIELD_SOURCE_ENTITY => $this->sourceEntityMock,
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
        ];

        return new ShippingContext($params);
    }
}
