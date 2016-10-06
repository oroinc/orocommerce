<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['name', 'test warehouse'],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $warehouse = new Warehouse();
        $this->assertPropertyAccessors($warehouse, $properties);
    }

    public function testPrePersist()
    {
        $warehouse = new Warehouse();
        $warehouse->prePersist();
        $this->assertInstanceOf('\DateTime', $warehouse->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $warehouse->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $warehouse = new Warehouse();
        $warehouse->preUpdate();
        $this->assertInstanceOf('\DateTime', $warehouse->getUpdatedAt());
    }

    public function testToString()
    {
        $warehouse = new Warehouse();
        $warehouse->setName('test warehouse');
        $this->assertEquals('test warehouse', (string) $warehouse);
    }
}
