<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Service should provide some supporting information about line items
 * that will be helpful during checkout creation and processing
 */
class CheckoutLineItemsProvider
{
    /**
     * Returns an array of ProductSku which were removed or have different quantity or unit
     *
     * @param Collection|ProductLineItemInterface[] $lineItems
     * @param Collection|ProductLineItemInterface[] $sourceLineItems
     * @return array
     */
    public function getProductSkusWithDifferences(Collection $lineItems, Collection $sourceLineItems)
    {
        $changed = [];

        foreach ($sourceLineItems as $sourceLineItem) {
            $found = false;

            foreach ($lineItems as $lineItem) {
                if ($sourceLineItem->getProductSku() === $lineItem->getProductSku() &&
                    $sourceLineItem->getProductUnitCode() === $lineItem->getProductUnitCode() &&
                    $sourceLineItem->getQuantity() === $lineItem->getQuantity()
                ) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $changed[] = $sourceLineItem->getProductSku();
            }
        }

        return $changed;
    }
}
