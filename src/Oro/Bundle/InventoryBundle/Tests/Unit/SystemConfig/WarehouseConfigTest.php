<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\SystemConfig;

use Oro\Bundle\WarehouseProBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseProBundle\SystemConfig\WarehouseConfig;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class WarehouseConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new WarehouseConfig(), [
            ['warehouse', new Warehouse()],
            ['priority', 42]
        ]);
    }

    public function testConstruct()
    {
        $config = new WarehouseConfig();
        $this->assertNull($config->getWarehouse());
        $this->assertNull($config->getPriority());

        $warehouse = new Warehouse();
        $priority = 100;

        $config = new WarehouseConfig($warehouse, $priority);
        $this->assertSame($warehouse, $config->getWarehouse());
        $this->assertSame($priority, $config->getPriority());
    }
}
