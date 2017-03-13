<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    protected $inventoryStatus;

    /**
     * @return mixed
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param AbstractEnumValue $inventoryStatus
     */
    public function setInventoryStatus(AbstractEnumValue $inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
