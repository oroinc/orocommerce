<?php

namespace Oro\Bundle\InventoryBundle\Tests\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WarehouseProBundle\Entity\Repository\WarehouseRepository;
use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

/**
 * @dbIsolation
 */
class WarehouseRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures(
            [
                LoadWarehousesAndInventoryLevels::class
            ]
        );
    }

    public function testCountAll()
    {
        /** @var WarehouseRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass(Warehouse::class)
            ->getRepository(Warehouse::class);

        $this->assertEquals(2, $repository->countAll());
    }

    public function testGetSingularWarehouse()
    {
        /** @var WarehouseRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass(Warehouse::class)
            ->getRepository(Warehouse::class);

        $warehouse = $repository->getSingularWarehouse();

        $this->assertEquals('First Warehouse', $warehouse->getName());
    }
}
