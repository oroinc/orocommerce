<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryStatusHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;

class InventoryStatusHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityFallbackResolver;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var InventoryStatusHandler */
    private $inventoryStatusHandler;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $this->inventoryStatusHandler = new InventoryStatusHandler(
            $this->entityFallbackResolver,
            $this->doctrineHelper
        );
    }

    public function testInventoryStatusWhenDecrementNotChange()
    {
        $inventoryThresholdValue = 5;
        $inventoryQuantity = 7;
        $inventoryLevel = $this->createMock(InventoryLevel::class);
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
        $inventoryLevel = $this->createMock(InventoryLevel::class);
        $inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);
        $this->entityFallbackResolver->expects($this->once())
            ->method('getFallbackValue')
            ->willReturn($inventoryThresholdValue);
        $inventoryRepository = $this->createMock(EnumValueRepository::class);
        $inventoryRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(new InventoryStatus(1, Product::INVENTORY_STATUS_OUT_OF_STOCK));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($inventoryRepository);
        $this->inventoryStatusHandler->changeInventoryStatusWhenDecrement($inventoryLevel);
    }
}
