<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;

/**
 * DTO to wrap product line items when only a part of line items should be taken into account.
 */
class ProductLineItemsHolderDTO implements
    ProductLineItemsHolderInterface,
    LineItemsNotPricedAwareInterface,
    WebsiteBasedCurrencyAwareInterface,
    CustomerOwnerAwareInterface
{
    /** @var Collection<ProductLineItemInterface> */
    private Collection $lineItems;

    private ?Website $website = null;

    private ?Customer $customer = null;

    private ?CustomerUser $customerUser = null;

    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
    }

    #[\Override]
    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    /**
     * @param Collection<ProductLineItemInterface> $lineItems
     *
     * @return self
     */
    public function setLineItems(Collection $lineItems): self
    {
        $this->lineItems = $lineItems;

        return $this;
    }

    public function addLineItem(ProductLineItemInterface $item): self
    {
        if (!$this->lineItems->contains($item)) {
            $this->lineItems->add($item);
        }

        return $this;
    }

    public function removeLineItem(ProductLineItemInterface $item): self
    {
        $this->lineItems->removeElement($item);

        return $this;
    }

    #[\Override]
    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    #[\Override]
    public function setWebsite(?Website $website): self
    {
        $this->website = $website;

        return $this;
    }

    #[\Override]
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    #[\Override]
    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }

    public function setCustomerUser(?CustomerUser $customerUser): self
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
