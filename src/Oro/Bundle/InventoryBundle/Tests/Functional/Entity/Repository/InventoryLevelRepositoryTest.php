<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\UpdateInventoryLevelsQuantities;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group CommunityEdition
 */
class InventoryLevelRepositoryTest extends WebTestCase
{
    /**
     * @var InventoryLevelRepository
     */
    protected $inventoryLevelRepo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            UpdateInventoryLevelsQuantities::class,
        ]);

        $this->inventoryLevelRepo = $this->client->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityRepository(InventoryLevel::class);
    }

    public function testGetLevelByProductAndProductUnit()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');

        $inventoryLevel = $this->inventoryLevelRepo->getLevelByProductAndProductUnit($product, $productUnit);
        $this->assertInstanceOf(InventoryLevel::class, $inventoryLevel);
        $this->assertEquals(
            self::processTemplateData('@inventory_level.product_unit_precision.product-1.liter->quantity'),
            $inventoryLevel->getQuantity()
        );

        /** @var Product $productReference */
        $product = $this->getReference('product-1');
        /** @var ProductUnit $unitReference */
        $productUnit = $this->getReference('product_unit.bottle');
        $inventoryLevel = $this->inventoryLevelRepo->getLevelByProductAndProductUnit($product, $productUnit);
        $this->assertInstanceOf(InventoryLevel::class, $inventoryLevel);
        $this->assertEquals(
            self::processTemplateData('@inventory_level.product_unit_precision.product-1.bottle->quantity'),
            $inventoryLevel->getQuantity()
        );
    }
}
