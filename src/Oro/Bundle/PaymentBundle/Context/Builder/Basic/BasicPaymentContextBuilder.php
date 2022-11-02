<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Creates PaymentContext with needed parameters
 */
class BasicPaymentContextBuilder implements PaymentContextBuilderInterface
{
    /**
     * @var AddressInterface
     */
    private $shippingAddress;

    /**
     * @var AddressInterface
     */
    private $shippingOrigin;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var Price
     */
    private $subTotal;

    /**
     * @var object
     */
    private $sourceEntity;

    /**
     * @var string
     */
    private $sourceEntityIdentifier;

    /**
     * @var array
     */
    private $lineItems = [];

    /**
     * @var AddressInterface
     */
    private $billingAddress;

    /**
     * @var string
     */
    private $shippingMethod;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var CustomerUser
     */
    private $customerUser;

    /**
     * @var PaymentLineItemCollectionFactoryInterface
     */
    private $paymentLineItemCollectionFactory;

    /**
     * @var Website
     */
    private $website;

    /**
     * @var float
     */
    private $total;

    /**
     * @param object                                    $sourceEntity
     * @param string                                    $sourceEntityIdentifier
     * @param PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
     */
    public function __construct(
        $sourceEntity,
        $sourceEntityIdentifier,
        PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
    ) {
        $this->sourceEntity = $sourceEntity;
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
        $this->paymentLineItemCollectionFactory = $paymentLineItemCollectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $params = $this->getMandatoryParams();
        $params += $this->getOptionalParams();

        return new PaymentContext($params);
    }

    /**
     * {@inheritDoc}
     */
    public function setLineItems(PaymentLineItemCollectionInterface $lineItemCollection)
    {
        $this->lineItems = $lineItemCollection->toArray();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLineItem(PaymentLineItemInterface $paymentLineItem)
    {
        $this->lineItems[] = $paymentLineItem;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setBillingAddress(AddressInterface $billingAddress)
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingAddress(AddressInterface $shippingAddress)
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingOrigin(AddressInterface $shippingOrigin)
    {
        $this->shippingOrigin = $shippingOrigin;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingMethod($shippingMethod)
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerUser(CustomerUser $customerUser)
    {
        $this->customerUser = $customerUser;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSubTotal(Price $subTotal)
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setWebsite(Website $website)
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return array
     */
    private function getMandatoryParams()
    {
        $lineItems = $this->paymentLineItemCollectionFactory->createPaymentLineItemCollection($this->lineItems);
        $params = [
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $this->sourceEntityIdentifier,
            PaymentContext::FIELD_LINE_ITEMS => $lineItems,
        ];

        return $params;
    }

    /**
     * @return array
     */
    private function getOptionalParams()
    {
        $optionalParams = [
            PaymentContext::FIELD_CURRENCY => $this->currency,
            PaymentContext::FIELD_SUBTOTAL => $this->subTotal,
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddress,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddress,
            PaymentContext::FIELD_SHIPPING_METHOD => $this->shippingMethod,
            PaymentContext::FIELD_CUSTOMER => $this->customer,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUser,
            PaymentContext::FIELD_WEBSITE => $this->website,
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOrigin,
            PaymentContext::FIELD_TOTAL => $this->total,
        ];

        // Exclude NULL elements.
        $optionalParams = array_diff_key($optionalParams, array_filter($optionalParams, 'is_null'));

        return $optionalParams;
    }
}
