<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory\ShippingLineItemCollectionFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class BasicShippingContextBuilder implements ShippingContextBuilderInterface
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
    private $paymentMethod;

    /**
     * @var Account
     */
    private $customer;

    /**
     * @var AccountUser
     */
    private $customerUser;

    /**
     * @var ShippingLineItemCollectionFactoryInterface
     */
    private $shippingLineItemCollectionFactory;

    /**
     * @var ShippingOriginProvider
     */
    private $shippingOriginProvider;

    /**
     * @param string $currency
     * @param Price $subTotal
     * @param object $sourceEntity
     * @param string $sourceEntityIdentifier
     * @param ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function __construct(
        $currency,
        Price $subTotal,
        $sourceEntity,
        $sourceEntityIdentifier,
        ShippingLineItemCollectionFactoryInterface $shippingLineItemCollectionFactory,
        ShippingOriginProvider $shippingOriginProvider
    ) {
        $this->currency = $currency;
        $this->subTotal = $subTotal;
        $this->sourceEntity = $sourceEntity;
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
        $this->shippingLineItemCollectionFactory = $shippingLineItemCollectionFactory;
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult()
    {
        $lineItems = $this->shippingLineItemCollectionFactory->createShippingLineItemCollection($this->lineItems);

        $shippingOrigin = null !== $this->shippingOrigin ?
            $this->shippingOrigin :
            $this->shippingOriginProvider->getSystemShippingOrigin();

        $params = [
            ShippingContext::FIELD_SHIPPING_ORIGIN => $shippingOrigin,
            ShippingContext::FIELD_CURRENCY => $this->currency,
            ShippingContext::FIELD_SUBTOTAL => $this->subTotal,
            ShippingContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            ShippingContext::FIELD_SOURCE_ENTITY_ID => $this->sourceEntityIdentifier,
            ShippingContext::FIELD_LINE_ITEMS => $lineItems,
        ];

        if (null !== $this->billingAddress) {
            $params[ShippingContext::FIELD_BILLING_ADDRESS] = $this->billingAddress;
        }

        if (null !== $this->shippingAddress) {
            $params[ShippingContext::FIELD_SHIPPING_ADDRESS] = $this->shippingAddress;
        }

        if (null !== $this->paymentMethod) {
            $params[ShippingContext::FIELD_PAYMENT_METHOD] = $this->paymentMethod;
        }

        if (null !== $this->customer) {
            $params[ShippingContext::FIELD_CUSTOMER] = $this->customer;
        }

        if (null !== $this->customerUser) {
            $params[ShippingContext::FIELD_CUSTOMER_USER] = $this->customerUser;
        }

        return new ShippingContext($params);
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
    public function setLineItems(ShippingLineItemCollectionInterface $lineItemCollection)
    {
        $this->lineItems = $lineItemCollection->toArray();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLineItem(ShippingLineItemInterface $shippingLineItem)
    {
        $this->lineItems[] = $shippingLineItem;

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
    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

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
