<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

use Oro\Bundle\WarehouseBundle\Validator\Constraints\UniqueWarehouse;

class UniqueWarehouseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UniqueWarehouse
     */
    protected $uniqueWarehouse;

    protected function setUp()
    {
        $this->uniqueWarehouse = new UniqueWarehouse();
    }

    public function testGetTargets()
    {
        $this->assertSame(Constraint::CLASS_CONSTRAINT, $this->uniqueWarehouse->getTargets());
    }

    public function testGetMessage()
    {
        $this->assertEquals('oro.warehouse.validators.unique_warehouse.message', $this->uniqueWarehouse->getMessage());
    }
}
