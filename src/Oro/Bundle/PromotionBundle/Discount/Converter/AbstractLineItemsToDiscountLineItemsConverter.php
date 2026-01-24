<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

/**
 * Provides common functionality for converting product line items to discount line items.
 *
 * This base class implements the core logic for creating {@see DiscountLineItem} instances from
 * product line items, copying essential product and quantity information. Subclasses must implement
 * the convert method to define specific conversion strategies for different line item sources.
 */
abstract class AbstractLineItemsToDiscountLineItemsConverter
{
    /**
     * @param ProductLineItemInterface[] $lineItems
     * @return array
     */
    abstract public function convert(array $lineItems): array;

    /**
     * @param ProductLineItemInterface $lineItem
     * @return null|DiscountLineItem
     */
    protected function createDiscountLineItem(ProductLineItemInterface $lineItem)
    {
        if (!$lineItem->getProduct()) {
            return null;
        }

        $discountLineItem = new DiscountLineItem();

        $discountLineItem->setQuantity($lineItem->getQuantity());
        $discountLineItem->setProduct($lineItem->getProduct());
        $discountLineItem->setProductUnit($lineItem->getProductUnit());
        $discountLineItem->setSourceLineItem($lineItem);

        return $discountLineItem;
    }
}
