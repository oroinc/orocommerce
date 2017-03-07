<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;

class LoadInventoryLevelDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $inventoryLevels = $this->getObjectReferences($manager, InventoryLevel::class);

        foreach ($inventoryLevels as $inventoryLevel) {
            $inventoryLevel->setQuantity(mt_rand(1, 100));
            $manager->persist($inventoryLevel);
        }

        $manager->flush();
    }
}
