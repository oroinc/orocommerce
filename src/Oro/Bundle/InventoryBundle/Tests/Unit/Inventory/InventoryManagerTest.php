<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\InventoryManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\Repository\OrganizationRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;

class InventoryManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var InventoryManager
     */
    protected $inventoryManager;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->inventoryManager = new InventoryManager($this->doctrineHelper);
    }

    public function testCreateInventoryLevel()
    {
        $product = new ProductStub();
        $product->inventoryStatus = new InventoryStatusStub(1, Product::INVENTORY_STATUS_OUT_OF_STOCK);
        $productUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $productUnitPrecision->expects($this->exactly(2))
            ->method('getProduct')
            ->willReturn($product);

        $organizationRepository = $this->createMock(OrganizationRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Organization::class)
            ->willReturn($organizationRepository);

        /** @var InventoryLevel $result */
        $result = $this->inventoryManager->createInventoryLevel($productUnitPrecision);

        $this->assertInstanceOf(InventoryLevel::class, $result);
        $this->assertEquals(
            Product::INVENTORY_STATUS_OUT_OF_STOCK,
            $result->getProduct()->inventoryStatus->getName()
        );
    }

    public function testCreateInventoryLevelNoProduct()
    {
        $productUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $productUnitPrecision->expects($this->once())
            ->method('getProduct')
            ->willReturn(null);

        $result = $this->inventoryManager->createInventoryLevel($productUnitPrecision);

        $this->assertNull($result);
    }

    public function testDeleteInventoryLevel()
    {
        $product = $this->createMock(Product::class);
        $productUnitPrecision = $this->createMock(ProductUnitPrecision::class);
        $productUnitPrecision->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $inventoryRepository = $this->getMockBuilder(InventoryLevelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $inventoryRepository->expects($this->once())
            ->method('deleteInventoryLevelByProductAndProductUnitPrecision')
            ->with($product, $productUnitPrecision);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($inventoryRepository)
            ->with(InventoryLevel::class);

        $this->inventoryManager->deleteInventoryLevel($productUnitPrecision);
    }
}
