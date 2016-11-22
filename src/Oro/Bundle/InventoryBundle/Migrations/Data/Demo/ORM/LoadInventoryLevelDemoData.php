<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class LoadInventoryLevelDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
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
        /** @var EntityManager $manager */
        $precisions = $this->getObjectReferences($manager, ProductUnitPrecision::class);

        foreach ($precisions as $precision) {
            $level = new InventoryLevel();
            $level
                ->setProductUnitPrecision($precision)
                ->setQuantity(mt_rand(1, 100));
            $manager->persist($level);
        }

        $manager->flush();
    }
}
