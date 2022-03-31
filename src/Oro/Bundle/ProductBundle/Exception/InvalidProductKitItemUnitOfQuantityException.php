<?php

namespace Oro\Bundle\ProductBundle\Exception;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Thrown when {@see ProductKitItem}::$productUnit contains a {@see ProductUnit} that is not valid because is not
 * present in each {@see Product}::$unitPrecisions of {@see ProductKitItem}::$products collection.
 */
class InvalidProductKitItemUnitOfQuantityException extends \RuntimeException
{
    private ProductKitItem $productKitItem;

    private ProductUnit $productUnit;

    private Product $product;

    public function __construct(ProductKitItem $productKitItem, ProductUnit $productUnit, Product $product)
    {
        $message = sprintf(
            'Invalid $productUnit is specified for %s (id: %d): '
            . 'product unit "%s" is not present in product (id: %d), '
            . 'but was expected to be present in each product in $products collection.',
            ProductKitItem::class,
            $productKitItem->getId(),
            $productUnit->getCode(),
            $product->getId()
        );

        parent::__construct($message);

        $this->productKitItem = $productKitItem;
        $this->productUnit = $productUnit;
        $this->product = $product;
    }

    public function getProductKitItem(): ProductKitItem
    {
        return $this->productKitItem;
    }

    public function getProductUnit(): ProductUnit
    {
        return $this->productUnit;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }
}
