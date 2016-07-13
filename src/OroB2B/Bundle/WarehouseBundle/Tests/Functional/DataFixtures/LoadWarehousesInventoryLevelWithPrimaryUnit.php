<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class LoadWarehousesInventoryLevelWithPrimaryUnit extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWarehousesAndInventoryLevels::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference(LoadWarehousesAndInventoryLevels::WAREHOUSE1);
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $level = new WarehouseInventoryLevel();
        $level
            ->setWarehouse($warehouse)
            ->setProductUnitPrecision($product->getPrimaryUnitPrecision())
            ->setQuantity(10);

        $manager->persist($level);
        $manager->flush();
    }
}
