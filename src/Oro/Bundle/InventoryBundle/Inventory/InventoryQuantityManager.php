<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager
{
    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     */
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
}
