<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\InventoryPreDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPreIncrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPostDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPostIncrementEvent;
use Oro\Bundle\InventoryBundle\Exception\InvalidInventoryLevelQuantityException;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager
{
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var EntityFallbackResolver
     */
    protected $entityFallbackResolver;

    /**
     * @param EventDispatcher $eventDispatcher
     * @param DoctrineHelper $doctrineHelper
     * @param EntityFallbackResolver $entityFallbackResolver
     */
    public function __construct(
        EventDispatcher $eventDispatcher,
        DoctrineHelper $doctrineHelper,
        EntityFallbackResolver $entityFallbackResolver
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityFallbackResolver = $entityFallbackResolver;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param $quantityToDecrement
     * @param bool $save
     * @throws InsufficientInventoryQuantityException
     */
    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement, $save = false)
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

        $preDecrementEvent = new InventoryPreDecrementEvent($inventoryLevel, $quantityToDecrement);
        $this->eventDispatcher->dispatch(InventoryPreDecrementEvent::NAME, $preDecrementEvent);

        if ($preDecrementEvent->isQuantityChanged()) {
            return;
        }

        $newQuantity = $initialQuantity - $quantityToDecrement;
        $inventoryLevel->setQuantity($newQuantity);

        //catch this event to check if we need to change the inventory status
        $afterDecrementEvent = new InventoryPostDecrementEvent($inventoryLevel, $quantityToDecrement);
        $this->eventDispatcher->dispatch(InventoryPostDecrementEvent::NAME, $afterDecrementEvent);

        if ($save) {
            $this->save($inventoryLevel);
        }
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param int $quantityToIncrement
     * @param bool $save
     * @throws InvalidInventoryLevelQuantityException
     */
    public function incrementQuantity(InventoryLevel $inventoryLevel, $quantityToIncrement, $save = false)
    {
        if (!is_numeric($quantityToIncrement) || $quantityToIncrement < 0) {
            throw new InvalidInventoryLevelQuantityException();
        }

        $event = new InventoryPreIncrementEvent($inventoryLevel, $quantityToIncrement);
        $this->eventDispatcher->dispatch($event::NAME, $event);
        if ($event->isQuantityChanged()) {
            return;
        }

        $inventoryLevel->setQuantity($inventoryLevel->getQuantity() + $quantityToIncrement);

        $event = new InventoryPostIncrementEvent($inventoryLevel, $quantityToIncrement);
        $this->eventDispatcher->dispatch($event::NAME, $event);

        if ($save) {
            $this->save($inventoryLevel);
        }
    }

    /**
     * @param InventoryLevel $inventoryLevel
     */
    protected function save(InventoryLevel $inventoryLevel)
    {
        $em = $this->doctrineHelper->getEntityManager($inventoryLevel);
        $em->flush($inventoryLevel);
    }
}
