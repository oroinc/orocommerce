<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;

class PaymentContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;

    /**
     * @var CustomerUser|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerUserMock;

    /**
     * @var PaymentLineItemCollectionInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subtotalMock;

    /**
     * @var Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sourceEntityMock;

    protected function setUp()
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
    }

    public function testConstructionAndGetters()
    {
        $shippingMethod = 'shippingMethod';
        $currency = 'usd';
        $entityId = '12';

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
        ];

        static::assertEquals($params, $getterValues);
    }
}
