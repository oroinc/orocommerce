<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Exception\InvalidInventoryLevelQuantityException;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager implements InventoryQuantityManagerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityFallbackResolver $entityFallbackResolver
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        EntityFallbackResolver $entityFallbackResolver
    ) {
        $this->eventDispatcher = $eventDispatcher;
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
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'backOrder')
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
     * @throws InvalidInventoryLevelQuantityException
     */
    public function incrementInventory(InventoryLevel $inventoryLevel, $quantityToIncrement)
    {
        if (!is_numeric($quantityToIncrement) || $quantityToIncrement < 0) {
            throw new InvalidInventoryLevelQuantityException();
        }

        $inventoryLevel->setQuantity($inventoryLevel->getQuantity() + $quantityToIncrement);
    }
}
