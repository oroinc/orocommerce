<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;

/**
 * Line item checksum generator that creates a checksum for the line item of a product kit.
 *
 * @deprecated since 5.1, use {@see \Oro\Bundle\ProductBundle\ProductKit\Checksum\ProductKitLineItemChecksumGenerator}
 * instead.
 */
class ProductKitLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    public function getChecksum(LineItem $lineItem): ?string
    {
        if (!$lineItem->getProduct()?->isKit()) {
            // Non-product-kit line item is not supported.
            return null;
        }

        // Ensures that checksum does not depend on the kit item line items ordering.
        $kitItemLineItemsIterator = $lineItem->getKitItemLineItems()->getIterator();
        $kitItemLineItemsIterator->uasort(
            static function (ProductKitItemLineItem $kitItemLineItem1, ProductKitItemLineItem $kitItemLineItem2) {
                return $kitItemLineItem2->getSortOrder() <=> $kitItemLineItem1->getSortOrder();
            }
        );

        $parts = [(int)$lineItem->getProduct()?->getId(), (string)$lineItem->getProductUnitCode()];
        foreach ($kitItemLineItemsIterator as $kitItemLineItem) {
            $parts[] = (int)$kitItemLineItem->getKitItem()?->getId();
            $parts[] = (int)$kitItemLineItem->getProduct()?->getId();
            $parts[] = (float)$kitItemLineItem->getQuantity();
            $parts[] = (string)$kitItemLineItem->getProductUnitCode();
        }

        return implode('|', $parts);
    }
}
