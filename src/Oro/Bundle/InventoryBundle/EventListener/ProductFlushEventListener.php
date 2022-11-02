<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductFlushEventListener
{
    /**
     * @var InventoryManager
     */
    protected $inventoryManager;

    public function __construct(InventoryManager $inventoryManager)
    {
        $this->inventoryManager = $inventoryManager;
    }

    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof ProductUnitPrecision) {
                $inventoryLevel = $this->inventoryManager->createInventoryLevel($entity);

                if ($inventoryLevel instanceof InventoryLevel) {
                    $em->persist($inventoryLevel);

                    $inventoryLevelMetadata = $em->getClassMetadata(get_class($inventoryLevel));
                    $uow->computeChangeSet($inventoryLevelMetadata, $inventoryLevel);
                }
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof ProductUnitPrecision) {
                $this->inventoryManager->deleteInventoryLevel($entity);
            }
        }
    }
}
