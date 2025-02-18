<?php

namespace Oro\Bundle\SaleBundle\ProductKit\Checksum;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;

/**
 * Provides available product prices for the specified quote product line item.
 */
class QuoteProductKitOfferLineItemChecksumGenerator implements LineItemChecksumGeneratorInterface
{
    private const int NEW = 0;
    private const int UPDATE = 1;

    public function getChecksum(ProductLineItemInterface $lineItem): ?string
    {
        if (!$lineItem instanceof QuoteProductOffer || !$lineItem->getProduct()?->isKit()) {
            // Non-quote-kit line item is not supported.
            return null;
        }

        $lineItem->loadKitItemLineItems();

        // Ensures that checksum does not depend on the quote kit item line items ordering.
        $kitItemLineItemsIterator = $lineItem->getKitItemLineItems()->getIterator();
        $kitItemLineItemsIterator->uasort(
            static function (
                ProductKitItemLineItemInterface $kitItemLineItem1,
                ProductKitItemLineItemInterface $kitItemLineItem2
            ) {
                return $kitItemLineItem2->getKitItem()?->getId() <=> $kitItemLineItem1->getKitItem()?->getId();
            }
        );

        $parts = [
            $lineItem->getId() ? self::UPDATE : self::NEW,
            (int)$lineItem->getProduct()?->getId(),
            (string)$this->getProductUnitCode($lineItem),
            (float)$lineItem->getPrice()?->getValue(),
            (string)$lineItem->getPrice()?->getCurrency()
        ];
        foreach ($kitItemLineItemsIterator as $kitItemLineItem) {
            if ($kitItemLineItem->getKitItemId() === null || $kitItemLineItem->getProductId() === null) {
                continue;
            }

            $parts[] = (int)$kitItemLineItem->getKitItem()?->getId();
            $parts[] = (int)$kitItemLineItem->getProduct()?->getId();
            $parts[] = (float)$kitItemLineItem->getQuantity();
            $parts[] = (string)$this->getProductUnitCode($kitItemLineItem);
        }

        return \implode('|', $parts);
    }

    private function getProductUnitCode(ProductLineItemInterface $lineItem): ?string
    {
        return $lineItem->getProductUnit()?->getCode() ?? $lineItem->getProductUnitCode();
    }
}
