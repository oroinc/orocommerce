<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class InventoryStatusEnumStub extends AbstractEnumValue
{
    protected $name;

    public function __construct($id, $name)
    {
        parent::__construct($id, $name);
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
