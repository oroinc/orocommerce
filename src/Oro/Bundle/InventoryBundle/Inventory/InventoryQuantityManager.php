<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager implements InventoryQuantityManagerInterface
{
    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     */
    public function __construct(
        EntityFallbackResolver $entityFallbackResolver
    ) {
        $this->entityFallbackResolver = $entityFallbackResolver;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToDecrement
     * @throws InsufficientInventoryQuantityException
     */
    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')) {
            return;
        }

        $initialQuantity = $inventoryLevel->getQuantity();
        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if (false === $this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
            && ($initialQuantity - $inventoryThreshold) < $quantityToDecrement
        ) {
            throw new InsufficientInventoryQuantityException();
        }

        $newQuantity = $initialQuantity - $quantityToDecrement;
        $inventoryLevel->setQuantity($newQuantity);
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param float $quantityToIncrement
     * @throws \LogicException
     */
    public function incrementInventory(InventoryLevel $inventoryLevel, $quantityToIncrement)
    {
        throw new \LogicException('Not implemented yet');
    }
}
