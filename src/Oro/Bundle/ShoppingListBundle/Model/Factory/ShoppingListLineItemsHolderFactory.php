<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Model\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ShoppingListBundle\Model\ShoppingListLineItemsHolder;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;

/**
 * Creates {@see ShoppingListLineItemsHolder} DTO to wrap line items when {@see ShoppingList} is not applicable,
 * e.g. when only a part of line items should be taken into account.
 */
class ShoppingListLineItemsHolderFactory
{
    /**
     * @param Collection<ProductLineItemInterface>|array<ProductLineItemInterface> $lineItems
     *
     * @return ShoppingListLineItemsHolder
     */
    public function createFromLineItems(Collection|array $lineItems): ShoppingListLineItemsHolder
    {
        if (!$lineItems instanceof Collection) {
            $lineItems = new ArrayCollection($lineItems);
        }

        if (!$lineItems->count()) {
            return new ShoppingListLineItemsHolder($lineItems);
        }

        $firstLineItem = $lineItems->first();
        $website = $customer = $customerUser = null;
        if ($firstLineItem instanceof ProductLineItemsHolderAwareInterface) {
            $lineItemsHolder = $firstLineItem->getLineItemsHolder();
            $website = $lineItemsHolder instanceof WebsiteAwareInterface ? $lineItemsHolder->getWebsite() : null;
            if ($lineItemsHolder instanceof CustomerOwnerAwareInterface) {
                $customer = $lineItemsHolder->getCustomer();
                $customerUser = $lineItemsHolder->getCustomerUser();
            }
        }

        return new ShoppingListLineItemsHolder($lineItems, $website, $customer, $customerUser);
    }
}
