<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

interface InventoryQuantityManagerInterface
{
    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToDecrement
     */
    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement);

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToIncrement
     */
    public function incrementInventory(InventoryLevel $inventoryLevel, $quantityToIncrement);
}
