<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\ShippingLineItemOptionsModifier;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;

/**
 * Creates:
 *  - instance of {@see ShippingKitItemLineItem} by {@see ProductKitItemLineItemPriceAwareInterface};
 *  - collection of {@see ShippingKitItemLineItem} by iterable {@see ProductKitItemLineItemPriceAwareInterface}.
 */
class ShippingKitItemLineItemFromProductKitItemLineItemFactory implements
    ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface
{
    public function __construct(
        private ShippingLineItemOptionsModifier $shippingLineItemOptionsModifier
    ) {
    }

    public function create(
        ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem
    ): ShippingKitItemLineItem {
        return $this->createShippingKitItemLineItem($productKitItemLineItem);
    }

    public function createCollection(iterable $productKitItemLineItems): Collection
    {
        $shippingKitItemLineItems = [];
        foreach ($productKitItemLineItems as $productKitItemLineItem) {
            $shippingKitItemLineItems[] = $this->createShippingKitItemLineItem($productKitItemLineItem);
        }

        return new ArrayCollection($shippingKitItemLineItems);
    }

    protected function createShippingKitItemLineItem(
        ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem
    ): ShippingKitItemLineItem {
        $shippingKitLineItem = (new ShippingKitItemLineItem($productKitItemLineItem))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setProductUnit($productKitItemLineItem->getProductUnit())
            ->setProductUnitCode($productKitItemLineItem->getProductUnitCode())
            ->setQuantity($productKitItemLineItem->getQuantity())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());

        $this->shippingLineItemOptionsModifier->modifyLineItemWithShippingOptions($shippingKitLineItem);

        return $shippingKitLineItem;
    }
}
