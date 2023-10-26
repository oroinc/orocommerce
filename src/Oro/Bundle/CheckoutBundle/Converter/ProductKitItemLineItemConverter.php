<?php

namespace Oro\Bundle\CheckoutBundle\Converter;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * Converts {@see ProductKitItemLineItemInterface} to the {@see CheckoutProductKitItemLineItem}.
 */
class ProductKitItemLineItemConverter
{
    public function convert(ProductKitItemLineItemInterface $kitItemLineItem): CheckoutProductKitItemLineItem
    {
        return (new CheckoutProductKitItemLineItem())
            ->setProduct($kitItemLineItem->getProduct())
            ->setKitItem($kitItemLineItem->getKitItem())
            ->setProductUnit($kitItemLineItem->getProductUnit())
            ->setQuantity($kitItemLineItem->getQuantity())
            ->setSortOrder($kitItemLineItem->getSortOrder())
            ->setPriceFixed(false);
    }
}
