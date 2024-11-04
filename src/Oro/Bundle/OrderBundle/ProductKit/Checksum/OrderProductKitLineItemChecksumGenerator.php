<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\ProductKit\Checksum;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Line item checksum generator that creates a checksum for the order line item of a product kit.
 */
class OrderProductKitLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    #[\Override]
    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        if (!$lineItem instanceof OrderLineItem || !$lineItem->getProduct()?->isKit()) {
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
            if ($kitItemLineItem->getKitItemId() === null || $kitItemLineItem->getProductId() === null) {
                continue;
            }

            $parts[] = (int)$kitItemLineItem->getKitItemId();
            $parts[] = (int)$kitItemLineItem->getProductId();
            $parts[] = (float)$kitItemLineItem->getQuantity();
            $parts[] = (string)$this->getProductUnitCode($kitItemLineItem);
            $parts[] = (float)$kitItemLineItem->getPrice()?->getValue();
            $parts[] = (string)$kitItemLineItem->getPrice()?->getCurrency();
        }

        return implode('|', $parts);
    }

    private function getProductUnitCode(ProductLineItemInterface $lineItem): ?string
    {
        return $lineItem->getProductUnit()?->getCode() ?? $lineItem->getProductUnitCode();
    }
}
