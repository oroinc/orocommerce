<?php

namespace Oro\Bundle\ShippingBundle\Context\Builder;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides an interface for shipping context builder.
 */
interface ShippingContextBuilderInterface
{
    public function getResult(): ShippingContextInterface;

    public function setLineItems(ShippingLineItemCollectionInterface $lineItemCollection): static;

    public function setBillingAddress(?AddressInterface $billingAddress): static;

    public function setShippingAddress(?AddressInterface $shippingAddress): static;

    public function setShippingOrigin(?AddressInterface $shippingOrigin): static;

    public function setPaymentMethod(?string $paymentMethod): static;

    public function setCustomer(?Customer $customer): static;

    public function setCustomerUser(?CustomerUser $customerUser): static;

    public function setSubTotal(?Price $subTotal): static;

    public function setCurrency(?string $currency): static;

    public function setWebsite(?Website $website): static;
}
