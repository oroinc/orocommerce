<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
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

        /** @var InventoryLevel $inventoryLevel */
        foreach ($inventoryLevels as $inventoryLevel) {
            $inventoryLevel->setQuantity(mt_rand(20, 200));
            $manager->persist($inventoryLevel);
        }

        $manager->flush();
    }
}
