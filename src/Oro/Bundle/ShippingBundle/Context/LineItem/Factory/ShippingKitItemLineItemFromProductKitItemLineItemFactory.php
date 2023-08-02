<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;

/**
 * Creates:
 *  - instance of {@see ShippingKitItemLineItem} by {@see ProductKitItemLineItemPriceAwareInterface};
 *  - collection of {@see ShippingKitItemLineItem} by iterable {@see ProductKitItemLineItemPriceAwareInterface}.
 */
class ShippingKitItemLineItemFromProductKitItemLineItemFactory implements
    ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface
{
    public function create(ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem): ShippingKitItemLineItem
    {
        return (new ShippingKitItemLineItem(
            $productKitItemLineItem->getProductUnit(),
            $productKitItemLineItem->getQuantity(),
            $productKitItemLineItem
        ))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());
    }

    public function createCollection(iterable $productKitItemLineItems): Collection
    {
        $shippingKitItemLineItems = [];
        foreach ($productKitItemLineItems as $productKitItemLineItem) {
            $shippingKitItemLineItems[] = $this->create($productKitItemLineItem);
        }

        return new ArrayCollection($shippingKitItemLineItems);
    }
}
