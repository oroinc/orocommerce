<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;
use Oro\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels as BaseFixture;

class LoadSingleWarehousesAndInventoryLevels extends BaseFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $user = $this->getFirstUser($manager);

        $this->createWarehouse($manager, 'First Warehouse', $user, self::WAREHOUSE1);

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

        $manager->flush();
    }
}
