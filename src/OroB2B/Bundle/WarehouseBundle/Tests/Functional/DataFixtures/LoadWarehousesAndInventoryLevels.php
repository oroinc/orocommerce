<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class LoadWarehousesAndInventoryLevels extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const WAREHOUSE1 = 'warehouse.1';
    const WAREHOUSE2 = 'warehouse.2';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);

        $this->createWarehouse($manager, 'First Warehouse', $user, self::WAREHOUSE1);
        $this->createWarehouse($manager, 'Second Warehouse', $user, self::WAREHOUSE2);

        $this->createWarehouseInventoryLevel($manager, self::WAREHOUSE1, 'product_unit_precision.product.1.liter', 10);
        $this->createWarehouseInventoryLevel($manager, self::WAREHOUSE1, 'product_unit_precision.product.1.bottle', 99);
        $this->createWarehouseInventoryLevel(
            $manager,
            self::WAREHOUSE1,
            'product_unit_precision.product.2.liter',
            12.345
        );
        $this->createWarehouseInventoryLevel($manager, self::WAREHOUSE1, 'product_unit_precision.product.2.bottle', 98);
        $this->createWarehouseInventoryLevel($manager, self::WAREHOUSE1, 'product_unit_precision.product.2.box', 42);
        $this->createWarehouseInventoryLevel(
            $manager,
            self::WAREHOUSE2,
            'product_unit_precision.product.2.box',
            98.765
        );

        $manager->flush();
    }

    /**
     * @param string $name
     * @param string $reference
     */
    protected function createWarehouse(ObjectManager $manager, $name, User $owner, $reference)
    {
        $warehouse = new Warehouse();
        $warehouse->setName($name)
            ->setOwner($owner->getBusinessUnits()->first())
            ->setOrganization($owner->getOrganization());

        $manager->persist($warehouse);
        $this->addReference($reference, $warehouse);
    }

    /**
     * @param ObjectManager $manager
     * @param string $warehouseReference
     * @param string $precisionReference
     * @param int|float $quantity
     */
    protected function createWarehouseInventoryLevel(
        ObjectManager $manager,
        $warehouseReference,
        $precisionReference,
        $quantity
    ) {
        /** @var Warehouse $warehouse */
        $warehouse = $this->getReference($warehouseReference);
        /** @var ProductUnitPrecision $precision */
        $precision = $this->getReference($precisionReference);

        $level = new WarehouseInventoryLevel();
        $level->setWarehouse($warehouse)
            ->setProductUnitPrecision($precision)
            ->setQuantity($quantity);

        $manager->persist($level);
        $this->addReference(
            sprintf('warehouse_inventory_level.%s.%s', $warehouseReference, $precisionReference),
            $level
        );
    }
}
