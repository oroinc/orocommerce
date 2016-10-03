<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

class LoadWarehousesInventoryLevelWithPrimaryUnit extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadWarehousesAndInventoryLevels::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var Product $product */
        $product = $this->getReference('product.1');

        $level = new InventoryLevel();
        $level
            ->setProductUnitPrecision($product->getPrimaryUnitPrecision())
            ->setQuantity(10);

        $manager->persist($level);
        $manager->flush();
    }
}
