<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteBasedCurrencyAwareInterface;

/**
 * DTO to wrap line items when {@see ShoppingList} is not applicable, e.g. when only a part of
 * line items should be taken into account.
 */
class ShoppingListLineItemsHolder implements
    ProductLineItemsHolderInterface,
    LineItemsNotPricedAwareInterface,
    WebsiteBasedCurrencyAwareInterface,
    CustomerOwnerAwareInterface
{
    /** @var Collection<ProductLineItemInterface> */
    private Collection $lineItems;

    private ?Website $website;

    private ?Customer $customer;

    private ?CustomerUser $customerUser;

    /**
     * @param Collection<ProductLineItemInterface> $lineItems
     * @param Website|null $website
     * @param Customer|null $customer
     * @param CustomerUser|null $customerUser
     */
    public function __construct(
        Collection $lineItems,
        Website $website = null,
        Customer $customer = null,
        CustomerUser $customerUser = null
    ) {
        $this->lineItems = $lineItems;
        $this->website = $website;
        $this->customer = $customer;
        $this->customerUser = $customerUser;
    }

    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    public function getWebsite(): ?Website
    {
        return $this->website;
    }

    public function setWebsite(?Website $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomerUser(): ?CustomerUser
    {
        return $this->customerUser;
    }
}
