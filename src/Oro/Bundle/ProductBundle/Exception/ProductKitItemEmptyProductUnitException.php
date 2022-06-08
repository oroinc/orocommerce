<?php

namespace Oro\Bundle\ProductBundle\Exception;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Thrown to prevent persisting a {@see ProductKitItem} with empty $productUnit.
 */
class ProductKitItemEmptyProductUnitException extends \RuntimeException
{
    private ProductKitItem $productKitItem;

    public function __construct(ProductKitItem $productKitItem)
    {
        parent::__construct('Product unit of ProductKitItem was not expected to be empty');

        $this->productKitItem = $productKitItem;
    }

    public function getProductKitItem(): ProductKitItem
    {
        return $this->productKitItem;
    }
}
