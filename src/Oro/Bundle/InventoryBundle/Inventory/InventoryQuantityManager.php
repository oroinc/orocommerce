<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\InventoryPostDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPreDecrementEvent;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;

class InventoryQuantityManager
{
    protected $eventDispatcher;
    protected $doctrineHelper;
    protected $entityFallbackResolver;

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
     */
    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'decrementQuantity')) {
            return;
        }

        $initialQuantity = $inventoryLevel->getQuantity();
        $inventoryThreshold = $this->entityFallbackResolver->getFallbackValue($product, 'inventoryThreshold');
        if (!$this->entityFallbackResolver->getFallbackValue($product, 'backOrder') &&
            ($initialQuantity - $inventoryThreshold) < $quantityToDecrement) {
            throw new InsufficientInventoryQuantityException();
        }

        $preDecrementEvent = new InventoryPreDecrementEvent($inventoryLevel, $quantityToDecrement);
        $this->eventDispatcher->dispatch(InventoryPreDecrementEvent::NAME, $preDecrementEvent);

        if ($preDecrementEvent->isPropagationStopped()) {
            return;
        }

        $newQuantity = $initialQuantity - $quantityToDecrement;
        $inventoryLevel->setQuantity($newQuantity);

        //catch this event to check if we need to change the inventory status
        $afterDecrementEvent = new InventoryPostDecrementEvent($inventoryLevel, $quantityToDecrement);
        $this->eventDispatcher->dispatch(InventoryPostDecrementEvent::NAME, $afterDecrementEvent);

        $em = $this->doctrineHelper->getEntityManager($inventoryLevel);
        $em->persist($inventoryLevel);
        $em->flush();
    }
}
