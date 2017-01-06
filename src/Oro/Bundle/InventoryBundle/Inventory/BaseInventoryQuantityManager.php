<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

abstract class BaseInventoryQuantityManager
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
    abstract function canDecrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement);

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
}
