<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\Checksum;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Line item checksum generator that creates a checksum for the line item of a product kit.
 */
class ProductKitLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        if (!$lineItem instanceof ProductKitItemLineItemsAwareInterface || !$lineItem->getProduct()?->isKit()) {
            // Non-product-kit line item is not supported.
            return null;
        }

        // Ensures that checksum does not depend on the kit item line items ordering.
        $kitItemLineItemsIterator = $lineItem->getKitItemLineItems()->getIterator();
        $kitItemLineItemsIterator->uasort(
            static function (
                ProductKitItemLineItemInterface $kitItemLineItem1,
                ProductKitItemLineItemInterface $kitItemLineItem2
            ) {
                return $kitItemLineItem2->getKitItem()?->getId() <=> $kitItemLineItem1->getKitItem()?->getId();
            }
        );

        $parts = [(int)$lineItem->getProduct()?->getId(), (string)$this->getProductUnitCode($lineItem)];
        foreach ($kitItemLineItemsIterator as $kitItemLineItem) {
            $parts[] = (int)$kitItemLineItem->getKitItem()?->getId();
            $parts[] = (int)$kitItemLineItem->getProduct()?->getId();
            $parts[] = (float)$kitItemLineItem->getQuantity();
            $parts[] = (string)$this->getProductUnitCode($kitItemLineItem);
        }

        return implode('|', $parts);
    }

    private function getProductUnitCode(ProductLineItemInterface $lineItem): ?string
    {
        return $lineItem->getProductUnit()?->getCode() ?? $lineItem->getProductUnitCode();
    }
}
