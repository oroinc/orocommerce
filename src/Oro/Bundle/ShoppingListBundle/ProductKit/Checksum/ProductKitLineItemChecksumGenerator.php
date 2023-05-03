<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Line item checksum generator that creates a checksum for the line item of a product kit.
 */
class ProductKitLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    public function getChecksum(LineItem $lineItem): ?string
    {
        if (!$lineItem->getProduct()?->isKit()) {
            // Non-product-kit line item is not supported.
            return null;
        }

        $parts = [(int)$lineItem->getProduct()?->getId(), (string)$lineItem->getProductUnitCode()];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $parts[] = (int)$kitItemLineItem->getKitItem()?->getId();
            $parts[] = (int)$kitItemLineItem->getProduct()?->getId();
            $parts[] = (float)$kitItemLineItem->getQuantity();
            $parts[] = (string)$kitItemLineItem->getProductUnitCode();
        }

        return implode('|', $parts);
    }
}
