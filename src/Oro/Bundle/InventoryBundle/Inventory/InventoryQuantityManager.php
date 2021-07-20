<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryQuantityManager
{
    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    public function __construct(EntityFallbackResolver $entityFallbackResolver)
    {
        $this->entityFallbackResolver = $entityFallbackResolver;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToDecrement
     * @return bool
     */
    public function canDecrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')) {
            return false;
        }

        return $this->checkQuantities($inventoryLevel, $quantityToDecrement, $product);
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param $quantityToDecrement
     * @return bool
     */
    public function hasEnoughQuantity(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')
            || $this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
        ) {
            return true;
        }

        return $this->checkQuantities($inventoryLevel, $quantityToDecrement, $product);
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToDecrement
     */
    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $inventoryLevel->setQuantity($inventoryLevel->getQuantity() - $quantityToDecrement);
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToIncrement
     */
    public function incrementInventory(InventoryLevel $inventoryLevel, $quantityToIncrement)
    {
        throw new \LogicException('Not implemented yet');

        $inventoryLevel->setQuantity($inventoryLevel->getQuantity() + $quantityToIncrement);
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function shouldDecrement(Product $product = null)
    {
        if (!$product instanceof Product) {
            return false;
        }

        if (!$this->entityFallbackResolver->getFallbackValue($product, 'manageInventory')) {
            return false;
        }

        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')) {
            return false;
        }

        return true;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param $quantityToDecrement
     * @param $product
     * @return bool
     */
    protected function checkQuantities(InventoryLevel $inventoryLevel, $quantityToDecrement, $product)
    {
        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if (false === $this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
            && ($inventoryLevel->getQuantity() - $inventoryThreshold) < $quantityToDecrement
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @return int
     */
    public function getAvailableQuantity(InventoryLevel $inventoryLevel)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->shouldDecrement($product)
            || $this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
        ) {
            return $inventoryLevel->getQuantity();
        }

        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');

        return $inventoryLevel->getQuantity() - $inventoryThreshold;
    }
}
