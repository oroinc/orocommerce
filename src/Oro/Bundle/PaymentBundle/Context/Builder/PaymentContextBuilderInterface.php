<?php

namespace Oro\Bundle\PaymentBundle\Context\Builder;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Provides an interface for payment context builder.
 */
interface PaymentContextBuilderInterface
{
    public function getResult(): PaymentContextInterface;

    /**
     * @param Collection<PaymentLineItem> $lineItemCollection
     *
     * @return $this
     */
    public function setLineItems(Collection $lineItemCollection): static;

    public function addLineItem(PaymentLineItem $paymentLineItem): static;

    public function setBillingAddress(?AddressInterface $billingAddress): static;

    public function setShippingAddress(?AddressInterface $shippingAddress): static;

    public function setShippingOrigin(?AddressInterface $shippingOrigin): static;

    public function setShippingMethod(?string $shippingMethod): static;

    public function setCustomer(?Customer $customer): static;

    public function setCustomerUser(?CustomerUser $customerUser): static;

    public function setSubTotal(?Price $subTotal): static;

    public function setCurrency(?string $currency): static;

    public function setWebsite(?Website $website): static;

    public function setTotal(?float $total): static;
}
