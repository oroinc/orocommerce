<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

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
        $order = new Warehouse();
        $order->prePersist();
        $this->assertInstanceOf('\DateTime', $order->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $order = new Warehouse();
        $order->preUpdate();
        $this->assertInstanceOf('\DateTime', $order->getUpdatedAt());
    }

    public function testToString()
    {
        $warehouse = new Warehouse();
        $warehouse->setName('test warehouse');
        $this->assertEquals('test warehouse', (string) $warehouse);
    }
}
