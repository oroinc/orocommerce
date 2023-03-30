<?php

namespace Oro\Bundle\ProductBundle\Exception;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Thrown when {@see ProductKitItem}::$productUnit contains a {@see ProductUnit} that is not present in
 * each {@see Product}::$unitPrecisions collection of the {@see ProductKitItem}::$products collection.
 */
class ProductKitItemInvalidProductUnitException extends \RuntimeException
{
    private ProductKitItem $productKitItem;

    private ProductUnit $productUnit;

    public function __construct(ProductKitItem $productKitItem, ProductUnit $productUnit)
    {
        $message = sprintf(
            'Product unit "%s" was expected to be present in each product of ProductKitItem $products collection.',
            $productUnit->getCode()
        );

        parent::__construct($message);

        $this->productKitItem = $productKitItem;
        $this->productUnit = $productUnit;
    }

    public function getProductKitItem(): ProductKitItem
    {
        return $this->productKitItem;
    }

    public function getProductUnit(): ProductUnit
    {
        return $this->productUnit;
    }
}
