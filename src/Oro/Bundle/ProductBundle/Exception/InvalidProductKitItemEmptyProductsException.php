<?php

namespace Oro\Bundle\ProductBundle\Exception;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Thrown when {@see ProductKitItem}::$products collection is empty.
 */
class InvalidProductKitItemEmptyProductsException extends \RuntimeException
{
    private ProductKitItem $productKitItem;

    public function __construct(ProductKitItem $productKitItem)
    {
        $message = sprintf(
            'Products collection of ProductKitItem (id: %d) was not expected to be empty.',
            $productKitItem->getId()
        );

        parent::__construct($message);

        $this->productKitItem = $productKitItem;
    }

    public function getProductKitItem(): ProductKitItem
    {
        return $this->productKitItem;
    }
}
