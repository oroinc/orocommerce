<?php

namespace Oro\Bundle\WarehouseBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\MigrationBundle\Fixture\AbstractEntityReferenceFixture;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class LoadWarehouseDemoData extends AbstractEntityReferenceFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const MAIN_WAREHOUSE = 'warehouse.main';
    const ADDITIONAL_WAREHOUSE = 'warehouse.additional.1';
    const ADDITIONAL_WAREHOUSE_2 = 'warehouse.additional.2';

    /**
     * @var array
     */
    protected $warehouses = [
        self::MAIN_WAREHOUSE => [
            'name' => 'Main Warehouse',
            'generateLevels' => true,
        ],
        self::ADDITIONAL_WAREHOUSE => [
            'name' => 'Additional Warehouse',
        ],
        self::ADDITIONAL_WAREHOUSE_2 => [
            'name' => 'Additional Warehouse 2',
        ]
    ];

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
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();
        $precisions   = $this->getObjectReferences($manager, 'OroProductBundle:ProductUnitPrecision');

        foreach ($this->warehouses as $reference => $row) {
            $warehouse = new Warehouse();
            $warehouse
                ->setName($row['name'])
                ->setOwner($businessUnit)
                ->setOrganization($organization);
            $manager->persist($warehouse);

            if (!empty($row['generateLevels'])) {
                foreach ($precisions as $precision) {
                    $level = new WarehouseInventoryLevel();
                    $level
                        ->setWarehouse($warehouse)
                        ->setProductUnitPrecision($precision)
                        ->setQuantity(mt_rand(1, 100));
                    $manager->persist($level);
                }
            }

            $this->addReference($reference, $warehouse);
        }

        $manager->flush();
    }
}
