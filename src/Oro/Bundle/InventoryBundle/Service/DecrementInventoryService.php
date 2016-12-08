<?php

namespace Oro\Bundle\InventoryBundle\Service;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\InventoryAfterDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPreDecrementEvent;

class DecrementInventoryService
{
    protected $eventDispatcher;
    protected $doctrineHelper;

    public function __construct(EventDispatcher $eventDispatcher, DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function decrementInventory(InventoryLevel $inventoryLevel, $quantityToDecrement)
    {
        $product = $inventoryLevel->getProduct();
        if (!$product->getDecrementQuantity()) {
            return;
        }

        $initialQuantity = $inventoryLevel->getQuantity();
        if (!$product->getBackOrder() && $initialQuantity < $quantityToDecrement) {
            //TODO: throw exception, what exception?
        }

        $preDecrementEvent = new InventoryPreDecrementEvent($inventoryLevel, $quantityToDecrement);
        $this->eventDispatcher->dispatch(InventoryPreDecrementEvent::NAME, $preDecrementEvent);

        if ($preDecrementEvent->isPropagationStopped()) {
            return;
        }

        $newQuantity = $initialQuantity - $quantityToDecrement;
        $inventoryLevel->setQuantity($newQuantity);

        //catch this event to check if we need to change the inventory status
        $afterDecrementEvent = new InventoryAfterDecrementEvent($inventoryLevel);
        $this->eventDispatcher->dispatch(InventoryAfterDecrementEvent::NAME, $afterDecrementEvent);

        $em = $this->doctrineHelper->getEntityManager($inventoryLevel);
        $em->persist($inventoryLevel);
        $em->flush();
    }
}
