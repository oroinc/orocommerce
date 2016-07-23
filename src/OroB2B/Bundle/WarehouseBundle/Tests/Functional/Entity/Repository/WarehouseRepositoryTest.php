<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\WarehouseBundle\Entity\Repository\WarehouseRepository;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;
use OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels;

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
