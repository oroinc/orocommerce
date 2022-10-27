<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryQuantityManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFallbackResolver;

    /** @var InventoryLevel|\PHPUnit\Framework\MockObject\MockObject */
    private $inventoryLevel;

    /** @var InventoryQuantityManager */
    private $inventoryQuantityManager;

    protected function setUp(): void
    {
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->inventoryLevel = $this->createMock(InventoryLevel::class);

        $this->inventoryQuantityManager = new InventoryQuantityManager($this->entityFallbackResolver);
    }

    public function testCanDecrementInventory()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->exactly(3))
            ->method('getFallbackValue')
            ->willReturnOnConsecutiveCalls(true, 0, false);
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->canDecrementInventory($this->inventoryLevel, 5);
    }

    public function testNoDecrementQuantity()
    {
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn(false);
        $this->inventoryLevel->expects($this->never())
            ->method('getQuantity');

        $this->inventoryQuantityManager->canDecrementInventory($this->inventoryLevel, 5);
    }

    public function testHasEnoughQuantity()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->exactly(4))
            ->method('getFallbackValue')
            ->willReturnOnConsecutiveCalls(true, false, 0, false);
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->hasEnoughQuantity($this->inventoryLevel, 5);
    }

    public function testBackOrderActive()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(true);
        $this->inventoryLevel->expects($this->never())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->hasEnoughQuantity($this->inventoryLevel, 5);
    }

    public function decrementInventory()
    {
        $inventoryQuantity = 10;
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);
        $this->inventoryLevel->expects($this->once())
            ->method('setQuantity');

        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, 5);
    }

    public function testShouldDecrementReturnTrue()
    {
        $product = $this->createMock(Product::class);
        $this->entityFallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product, 'manageInventory', 1, true],
                [$product, 'decrementQuantity', 1, true]
            ]);

        $this->assertTrue($this->inventoryQuantityManager->shouldDecrement($product));
    }

    public function testShouldDecrementReturnFalse()
    {
        $product = $this->createMock(Product::class);
        $this->entityFallbackResolver->expects($this->any())
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product, 'manageInventory', 1, true],
                [$product, 'decrementQuantity', 1, false]
            ]);

        $this->assertFalse($this->inventoryQuantityManager->shouldDecrement($product));
        $this->assertFalse($this->inventoryQuantityManager->shouldDecrement(null));
    }

    public function testGetAvailableQuantity()
    {
        $product = new Product();

        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->entityFallbackResolver->expects($this->exactly(4))
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product, 'manageInventory', 1, true],
                [$product, 'decrementQuantity', 1, true],
                [$product, 'backOrder', 1, false],
                [$product, 'inventoryThreshold', 1, 3],
            ]);

        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn(10);

        $this->assertEquals(
            7,
            $this->inventoryQuantityManager->getAvailableQuantity($this->inventoryLevel)
        );
    }

    public function testGetAvailableQuantityWithoutDecrementQuantity()
    {
        $product = new Product();

        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product, 'decrementQuantity', 1, false],
            ]);

        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn(15);

        $this->assertEquals(
            15,
            $this->inventoryQuantityManager->getAvailableQuantity($this->inventoryLevel)
        );
    }

    public function testGetAvailableQuantityWithBackOrder()
    {
        $product = new Product();

        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);

        $this->entityFallbackResolver->expects($this->exactly(3))
            ->method('getFallbackValue')
            ->willReturnMap([
                [$product, 'manageInventory', 1, true],
                [$product, 'decrementQuantity', 1, true],
                [$product, 'backOrder', 1, true],
            ]);

        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn(15);

        $this->assertEquals(
            15,
            $this->inventoryQuantityManager->getAvailableQuantity($this->inventoryLevel)
        );
    }
}
