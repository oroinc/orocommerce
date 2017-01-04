<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Factory\PaymentLineItemCollectionFactoryInterface;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;

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
     * @var Account
     */
    private $customer;

    /**
     * @var AccountUser
     */
    private $customerUser;

    /**
     * @var PaymentLineItemCollectionFactoryInterface
     */
    private $paymentLineItemCollectionFactory;

    /**
     * @param string $currency
     * @param Price $subTotal
     * @param object $sourceEntity
     * @param string $sourceEntityIdentifier
     * @param PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
     */
    public function __construct(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityIdentifier,
        PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
    ) {
        $this->currency = $currency;
        $this->subTotal = $subTotal;
        $this->sourceEntity = $sourceEntity;
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
        $this->paymentLineItemCollectionFactory = $paymentLineItemCollectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $lineItems = $this->paymentLineItemCollectionFactory->createPaymentLineItemCollection($this->lineItems);

        $params = [
            PaymentContext::FIELD_CURRENCY => $this->currency,
            PaymentContext::FIELD_SUBTOTAL => $this->subTotal,
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $this->sourceEntityIdentifier,
            PaymentContext::FIELD_LINE_ITEMS => $lineItems,
        ];

        if (null !== $this->billingAddress) {
            $params[PaymentContext::FIELD_BILLING_ADDRESS] = $this->billingAddress;
        }

        if (null !== $this->shippingAddress) {
            $params[PaymentContext::FIELD_SHIPPING_ADDRESS] = $this->shippingAddress;
        }

        if (null !== $this->shippingMethod) {
            $params[PaymentContext::FIELD_SHIPPING_METHOD] = $this->shippingMethod;
        }

        if (null !== $this->customer) {
            $params[PaymentContext::FIELD_CUSTOMER] = $this->customer;
        }

        if (null !== $this->customerUser) {
            $params[PaymentContext::FIELD_CUSTOMER_USER] = $this->customerUser;
        }

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
    public function setCustomer(Account $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerUser(AccountUser $customerUser)
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
