<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class LoadWarehouseDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData',
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        $precisions   = $this->getObjectReferences($manager, ProductUnitPrecision::class);

        foreach ($precisions as $precision) {
            $level = new WarehouseInventoryLevel();
            $level
                ->setProductUnitPrecision($precision)
                ->setQuantity(mt_rand(1, 100));
            $manager->persist($level);
        }

        $manager->flush();
    }
}
