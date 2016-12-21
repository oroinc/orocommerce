<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;

class ShippingContextTest extends \PHPUnit_Framework_TestCase
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

    protected function setUp()
    {
        $this->customerMock = $this->getMockBuilder(Account::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerUserMock = $this->getMockBuilder(AccountUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->lineItemsCollectionMock = $this->createMock(ShippingLineItemCollectionInterface::class);
        $this->billingAddressMock = $this->createMock(AddressInterface::class);
        $this->shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->shippingOriginMock = $this->createMock(AddressInterface::class);
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sourceEntityMock = $this->getMockBuilder(Checkout::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testConstructionAndGetters()
    {
        $paymentMethod = 'paymentMethod';
        $currency = 'usd';
        $entityId = '12';

        $params = [
            ShippingContext::FIELD_CUSTOMER => $this->customerMock,
            ShippingContext::FIELD_CUSTOMER_USER => $this->customerUserMock,
            ShippingContext::FIELD_LINE_ITEMS => $this->lineItemsCollectionMock,
            ShippingContext::FIELD_BILLING_ADDRESS => $this->billingAddressMock,
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddressMock,
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->shippingOriginMock,
            ShippingContext::FIELD_PAYMENT_METHOD => $paymentMethod,
            ShippingContext::FIELD_CURRENCY => $currency,
            ShippingContext::FIELD_SUBTOTAL => $this->subtotalMock,
            ShippingContext::FIELD_SOURCE_ENTITY => $this->sourceEntityMock,
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $entityId,
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
        ];

        $this->assertEquals($params, $getterValues);
    }
}
