<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;

/**
 * Ensures that {@see ProductKitItemProduct::$productUnitPrecision} is up-to-date with its product and kit item.
 */
class ProductKitItemProductUnitPrecisionDoctrineListener
{
    public function prePersist(ProductKitItemProduct $kitItemProduct): void
    {
        $kitItemProduct->updateProductUnitPrecision();
    }

    public function preUpdate(ProductKitItemProduct $kitItemProduct): void
    {
        $kitItemProduct->updateProductUnitPrecision();
    }
}
