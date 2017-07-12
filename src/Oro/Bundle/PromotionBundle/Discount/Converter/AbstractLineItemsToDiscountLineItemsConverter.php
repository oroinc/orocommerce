<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;

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
