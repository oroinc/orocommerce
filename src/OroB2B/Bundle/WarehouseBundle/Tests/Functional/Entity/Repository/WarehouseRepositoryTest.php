<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\WarehouseBundle\Entity\Repository\WarehouseRepository;

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
                'OroB2B\Bundle\WarehouseBundle\Tests\Functional\DataFixtures\LoadWarehousesAndInventoryLevels'
            ]
        );
    }

    public function testCountAll()
    {
        $warehouseClass = 'OroB2B\Bundle\WarehouseBundle\Entity\Warehouse';

        /** @var WarehouseRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getManagerForClass($warehouseClass)
            ->getRepository($warehouseClass);

        $this->assertEquals(2, $repository->countAll());
    }
}
