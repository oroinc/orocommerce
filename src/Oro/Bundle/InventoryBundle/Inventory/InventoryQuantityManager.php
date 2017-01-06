<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager extends BaseInventoryQuantityManager
{
    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToDecrement
     * @throws InsufficientInventoryQuantityException
     * @return bool
     */
    public function canDecrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')) {
            return false;
        }

        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if (false === $this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
            && ($inventoryLevel->getQuantity() - $inventoryThreshold) < $quantityToDecrement
        ) {
            return false;
        }

        return true;
    }
}
