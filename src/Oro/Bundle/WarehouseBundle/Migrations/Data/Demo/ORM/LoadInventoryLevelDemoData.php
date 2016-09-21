<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

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
        $precisions = $this->getObjectReferences($manager, 'OroProductBundle:ProductUnitPrecision');

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
