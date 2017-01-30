<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadInventoryLevels;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class InventoryLevelRepositoryTest extends WebTestCase
{
    /**
     * @var InventoryLevelRepository
     */
    protected $inventoryLevelRepo;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadInventoryLevels::class,
        ]);

        $this->inventoryLevelRepo = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(InventoryLevel::class);
    }

    public function testGetLevelByProductAndProductUnit()
    {
        /** @var Product $productReference */
        $product = $this->getReference('product-1');
        /** @var ProductUnit $unitReference */
        $productUnit = $this->getReference('product_unit.liter');

        $inventoryLevel = $this->inventoryLevelRepo->getLevelByProductAndProductUnit($product, $productUnit);
        $this->assertInstanceOf(InventoryLevel::class, $inventoryLevel);
        $this->assertEquals(LoadInventoryLevels::PRECISION_LITER_QTY_10, $inventoryLevel->getQuantity());

        /** @var Product $productReference */
        $product = $this->getReference('product-1');
        /** @var ProductUnit $unitReference */
        $productUnit = $this->getReference('product_unit.bottle');
        $inventoryLevel = $this->inventoryLevelRepo->getLevelByProductAndProductUnit($product, $productUnit);
        $this->assertInstanceOf(InventoryLevel::class, $inventoryLevel);
        $this->assertEquals(LoadInventoryLevels::PRECISION_BOTTLE_QTY_99, $inventoryLevel->getQuantity());
    }
}
