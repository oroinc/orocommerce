<?php

namespace Oro\Bundle\InventoryBundle\Inventory;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\InventoryPreIncrementEvent;
use Oro\Bundle\InventoryBundle\Exception\InvalidInventoryLevelQuantityException;

class IncrementService
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(DoctrineHelper $doctrineHelper, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param InventoryLevel $inventoryLevel
     * @param $quantityToIncrement
     * @throws InvalidInventoryLevelQuantityException
     */
    public function incrementQuantity(InventoryLevel $inventoryLevel, $quantityToIncrement)
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
    }
}
