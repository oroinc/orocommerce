<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcher;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Event\InventoryPostDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPostIncrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPreDecrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryPreIncrementEvent;
use Oro\Bundle\InventoryBundle\Event\InventoryQuantityChangeEvent;
use Oro\Bundle\InventoryBundle\Exception\InsufficientInventoryQuantityException;
use Oro\Bundle\InventoryBundle\Exception\InvalidInventoryLevelQuantityException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryQuantityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventDispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFallbackResolver;

    /**
     * @var InventoryQuantityManager
     */
    protected $inventoryQuantityManager;

    /**
     * @var InventoryLevel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryLevel;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock(EventDispatcher::class);
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var InventoryLevel|\PHPUnit_Framework_MockObject_MockObject $inventoryLevel * */
        $this->inventoryLevel = $this->getMock(InventoryLevel::class);
        $this->inventoryQuantityManager = new InventoryQuantityManager(
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->entityFallbackResolver
        );
    }

    public function testDecrementInventoryShouldNotContinueIfNotProduct()
    {
        $this->eventDispatcher->expects($this->never())->method('dispatch');
        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, 2);
    }

    public function testDecrementInventoryShouldThrowInsufficienQuantityException()
    {
        $quantityToDecrement = 2;
        $this->setUpCorrectDecrementConditions(5, 4, false);

        $this->setExpectedException(InsufficientInventoryQuantityException::class);
        $this->eventDispatcher->expects($this->never())->method('dispatch');

        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, $quantityToDecrement);
    }

    public function testDecrementInventoryStopsIfQuantityChangedByEvent()
    {
        $this->setUpCorrectDecrementConditions(5, 2);
        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->willReturnCallback(
                function ($eventName, InventoryQuantityChangeEvent $event) {
                    $event->setQuantityChanged(true);
                }
            );
        $this->inventoryLevel->expects($this->never())->method('setQuantity');
        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, 2);
    }

    public function testDecrementInventoryExecutesDecrement()
    {
        $initialQuantity = 5;
        $quantityToDecrement = 3;
        $this->setUpCorrectDecrementConditions($initialQuantity, 2);
        $this->eventDispatcher->expects($this->at(0))->method('dispatch')
            ->willReturnCallback(
                function ($eventName, InventoryQuantityChangeEvent $event) {
                    $this->assertEquals(InventoryPreDecrementEvent::NAME, $eventName);
                    $event->setQuantityChanged(false);
                }
            );
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch');
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName) {
                    $this->assertEquals(InventoryPostDecrementEvent::NAME, $eventName);
                }
            );
        $this->inventoryLevel->expects($this->once())
            ->method('setQuantity')
            ->willReturnCallback(
                function ($newQuantity) {
                    $this->assertEquals(2, $newQuantity);
                }
            );
        $this->assertEntityManagerSave();
        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, $quantityToDecrement, true);
    }

    public function testIncrementQuantityThrowsExceptionOnInvalidQuantity()
    {
        $this->setExpectedException(InvalidInventoryLevelQuantityException::class);
        $this->inventoryQuantityManager->incrementQuantity($this->inventoryLevel, 'xxx');
    }

    public function testIncrementQuantityThrowsExceptionOnNegativeQuantity()
    {
        $this->setExpectedException(InvalidInventoryLevelQuantityException::class);
        $this->inventoryQuantityManager->incrementQuantity($this->inventoryLevel, -4);
    }

    public function testIncrementQuantityStopsIfQuantityChanged()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, InventoryQuantityChangeEvent $event) {
                    $this->assertEquals(InventoryPreIncrementEvent::NAME, $eventName);
                    $event->setQuantityChanged(true);
                }
            );
        $this->inventoryLevel->expects($this->never())->method('setQuantity');
        $this->inventoryQuantityManager->incrementQuantity($this->inventoryLevel, 4);
    }

    public function testIncrementQuantityExecutesAndSaves()
    {
        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch');
        $initialQuantity = 4;
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName, InventoryQuantityChangeEvent $event) {
                    $this->assertEquals(InventoryPreIncrementEvent::NAME, $eventName);
                    $event->setQuantityChanged(false);
                }
            );
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->willReturnCallback(
                function ($eventName) {
                    $this->assertEquals(InventoryPostIncrementEvent::NAME, $eventName);
                }
            );

        $this->inventoryLevel->expects($this->once())->method('getQuantity')->willReturn($initialQuantity);
        $this->inventoryLevel->expects($this->once())
            ->method('setQuantity')
            ->willReturnCallback(
                function ($quantity) {
                    $this->assertEquals(7, $quantity);
                }
            );
        $this->assertEntityManagerSave();
        $this->inventoryQuantityManager->incrementQuantity($this->inventoryLevel, 3, true);
    }

    /**
     * @param int $initialQuantity
     * @param int $inventoryThreshold
     * @param bool $isBackOrder
     */
    protected function setUpCorrectDecrementConditions($initialQuantity, $inventoryThreshold, $isBackOrder = true)
    {
        $this->inventoryLevel->expects($this->once())->method('getQuantity')->willReturn($initialQuantity);
        $product = new Product();
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(true);
        $this->entityFallbackResolver->expects($this->at(1))
            ->method('getFallbackValue')
            ->willReturn($inventoryThreshold);
        $this->entityFallbackResolver->expects($this->at(2))
            ->method('getFallbackValue')
            ->willReturn($isBackOrder);
    }

    protected function assertEntityManagerSave()
    {
        $em = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->doctrineHelper->expects($this->once())->method('getEntityManager')->willReturn($em);
        $em->expects($this->once())->method('flush')->with($this->inventoryLevel);
    }
}
