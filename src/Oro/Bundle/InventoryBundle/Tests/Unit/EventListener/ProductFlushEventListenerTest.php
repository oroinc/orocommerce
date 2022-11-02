<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\EventListener\ProductFlushEventListener;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * @group CommunityEdition
 */
class ProductFlushEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InventoryManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $inventoryManager;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityManager;

    /**
     * @var ProductFlushEventListener
     */
    protected $productFlushEventListener;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->inventoryManager = $this->getMockBuilder(InventoryManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productFlushEventListener = new ProductFlushEventListener($this->inventoryManager);
    }

    public function testOnFlush()
    {
        $eventArgs = $this->prepareEvent();
        $classMetaData = new ClassMetadata(InventoryLevel::class);
        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $this->inventoryManager->expects($this->once())
            ->method('createInventoryLevel')
            ->willReturn($inventoryLevel);
        $this->inventoryManager->expects($this->once())
            ->method('deleteInventoryLevel');
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($inventoryLevel);
        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetaData);

        $this->productFlushEventListener->onFlush($eventArgs);
    }

    public function testOnFlushNoProductUnitPrecision()
    {
        $eventArgs = $this->prepareEvent(false, false);
        $this->inventoryManager->expects($this->never())
            ->method('createInventoryLevel');
        $this->inventoryManager->expects($this->never())
            ->method('deleteInventoryLevel');
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('getClassMetadata');

        $this->productFlushEventListener->onFlush($eventArgs);
    }

    public function testOnFlushNoInventoryLevel()
    {
        $eventArgs = $this->prepareEvent(true, true, false);
        $this->inventoryManager->expects($this->once())
            ->method('createInventoryLevel')
            ->willReturn(null);
        $this->inventoryManager->expects($this->once())
            ->method('deleteInventoryLevel');
        $this->entityManager->expects($this->never())
            ->method('persist');
        $this->entityManager->expects($this->never())
            ->method('getClassMetadata');

        $this->productFlushEventListener->onFlush($eventArgs);
    }

    /**
     * @param bool $insertEntities
     * @param bool $deleteEntities
     * @param bool $hasProduct
     *
     * @return OnFlushEventArgs
     */
    protected function prepareEvent($insertEntities = true, $deleteEntities = true, $hasProduct = true)
    {
        $eventArgs = new OnFlushEventArgs($this->entityManager);
        $entity = $this->createMock(ProductUnitPrecision::class);
        $unitOfWork = $this->getMockBuilder(UnitOfWork::class)
            ->disableOriginalConstructor()
            ->getMock();
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertEntities ? [$entity] : []);
        $unitOfWork->expects($this->once())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deleteEntities ? [$entity] : []);
        $unitOfWork->expects($insertEntities && $hasProduct ? $this->once() : $this->never())
            ->method('computeChangeSet');
        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $eventArgs;
    }
}
