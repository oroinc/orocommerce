<?php
declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductUnitPrecisionDemoData;

/**
 * Populates inventory levels for all product - unit combinations
 */
class LoadInventoryLevelDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    public function getDependencies(): array
    {
        return [
            LoadProductUnitPrecisionDemoData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $inventoryLevels = $this->getObjectReferences($manager, InventoryLevel::class);

        /** @var InventoryLevel $inventoryLevel */
        foreach ($inventoryLevels as $inventoryLevel) {
            $inventoryLevel->setQuantity(\mt_rand(1000, 10000));
            $manager->persist($inventoryLevel);
        }

        $manager->flush();
    }
}
