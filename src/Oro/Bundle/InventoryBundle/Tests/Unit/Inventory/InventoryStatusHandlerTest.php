<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;

class InventoryStatusHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityFallbackResolver;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var InventoryStatusHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $inventoryStatusHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->inventoryStatusHandler = new InventoryStatusHandler(
            $this->entityFallbackResolver,
            $this->doctrineHelper
        );
    }

    public function testInventoryStatusWhenDecrementNotChange()
    {
        $inventoryThresholdValue = 5;
        $inventoryQuantity = 7;
        $inventoryLevel = $this->getMockBuilder(InventoryLevel::class)->getMock();
        $inventoryLevel->expects($this->once())
            ->method('getProduct');
        $inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);
        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn($inventoryThresholdValue);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');
        $this->inventoryStatusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
    }

    public function testInventoryStatusWhenDecrementChange()
    {
        $inventoryThresholdValue = 5;
        $inventoryQuantity = 5;
        $product = new ProductStub();
        $inventoryLevel = $this->getMockBuilder(InventoryLevel::class)->getMock();
        $inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);
        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn($inventoryThresholdValue);
        $inventoryRepository = $this->getMockBuilder(EnumValueRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new InventoryStatusStub(1, Product::INVENTORY_STATUS_OUT_OF_STOCK));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($inventoryRepository);
        $this->inventoryStatusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
    }
}
