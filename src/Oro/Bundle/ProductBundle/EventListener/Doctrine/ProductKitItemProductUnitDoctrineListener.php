<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;

/**
 * Calls {@see ProductKitItemProduct::updateProductUnitPrecision} for the kit item products
 * if {@see ProductKitItem::$productUnit} is changed.
 */
class ProductKitItemProductUnitDoctrineListener
{
    public function preUpdate(ProductKitItem $productKitItem, PreUpdateEventArgs $eventArgs): void
    {
        if ($eventArgs->hasChangedField('productUnit')) {
            foreach ($productKitItem->getKitItemProducts() as $kitItemProduct) {
                $kitItemProduct->updateProductUnitPrecision();
            }
        }
    }
}
