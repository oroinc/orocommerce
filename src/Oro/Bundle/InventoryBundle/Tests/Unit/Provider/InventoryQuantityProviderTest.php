<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class InventoryQuantityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var InventoryQuantityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $quantityManager;

    /** @var InventoryQuantityProvider */
    protected $provider;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->quantityManager = $this->createMock(InventoryQuantityManager::class);

        $this->provider = new InventoryQuantityProvider($this->doctrineHelper, $this->quantityManager);
    }

    public function testCanDecrement()
    {
        $product = new Product();

        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->with($product)
            ->willReturn(true);

        $this->assertTrue($this->provider->canDecrement($product));
    }

    public function testCanDecrementWithoutProduct()
    {
        $this->quantityManager->expects($this->once())
            ->method('shouldDecrement')
            ->with(null)
            ->willReturn(false);

        $this->assertFalse($this->provider->canDecrement(null));
    }

    public function testGetAvailableQuantity()
    {
        $product = new Product();
        $productUnit = new ProductUnit();

        $inventoryLevelRepository = $this->createMock(InventoryLevelRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(InventoryLevel::class)
            ->willReturn($inventoryLevelRepository);
        $level1 = $this->createMock(InventoryLevel::class);

        $inventoryLevelRepository->expects($this->once())
            ->method('getLevelByProductAndProductUnit')
            ->with($product, $productUnit)
            ->willReturn($level1);

        $this->quantityManager->expects($this->once())
            ->method('getAvailableQuantity')
            ->with($level1)
            ->willReturn(123);

        $this->assertEquals(
            123,
            $this->provider->getAvailableQuantity($product, $productUnit)
        );
    }
}
