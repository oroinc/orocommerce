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
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItemInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * The basic implementation of payment context builder.
 */
class BasicPaymentContextBuilder implements PaymentContextBuilderInterface
{
    private object $sourceEntity;
    private mixed $sourceEntityIdentifier;
    private PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory;
    private array $lineItems = [];
    private ?AddressInterface $billingAddress = null;
    private ?AddressInterface $shippingAddress = null;
    private ?AddressInterface $shippingOrigin = null;
    private ?string $shippingMethod = null;
    private ?Customer $customer = null;
    private ?CustomerUser $customerUser = null;
    private ?Price $subTotal = null;
    private ?string $currency = null;
    private ?Website $website = null;
    private ?float $total = null;

    public function __construct(
        object $sourceEntity,
        mixed $sourceEntityIdentifier,
        PaymentLineItemCollectionFactoryInterface $paymentLineItemCollectionFactory
    ) {
        $this->sourceEntity = $sourceEntity;
        $this->sourceEntityIdentifier = $sourceEntityIdentifier;
        $this->paymentLineItemCollectionFactory = $paymentLineItemCollectionFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function getResult(): PaymentContextInterface
    {
        $params = $this->getMandatoryParams();
        $params += $this->getOptionalParams();

        return new PaymentContext($params);
    }

    /**
     * {@inheritDoc}
     */
    public function setLineItems(PaymentLineItemCollectionInterface $lineItemCollection): static
    {
        $this->lineItems = $lineItemCollection->toArray();

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addLineItem(PaymentLineItemInterface $paymentLineItem): static
    {
        $this->lineItems[] = $paymentLineItem;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setBillingAddress(?AddressInterface $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingAddress(?AddressInterface $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingOrigin(?AddressInterface $shippingOrigin): static
    {
        $this->shippingOrigin = $shippingOrigin;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setShippingMethod(?string $shippingMethod): static
    {
        $this->shippingMethod = $shippingMethod;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCustomerUser(?CustomerUser $customerUser): static
    {
        $this->customerUser = $customerUser;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setSubTotal(?Price $subTotal): static
    {
        $this->subTotal = $subTotal;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setCurrency(?string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setWebsite(?Website $website): static
    {
        $this->website = $website;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function setTotal(?float $total): static
    {
        $this->total = $total;

        return $this;
    }

    private function getMandatoryParams(): array
    {
        return [
            PaymentContext::FIELD_SOURCE_ENTITY => $this->sourceEntity,
            PaymentContext::FIELD_SOURCE_ENTITY_ID => $this->sourceEntityIdentifier,
            PaymentContext::FIELD_LINE_ITEMS => $this->paymentLineItemCollectionFactory
                ->createPaymentLineItemCollection($this->lineItems)
        ];
    }

    private function getOptionalParams(): array
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
