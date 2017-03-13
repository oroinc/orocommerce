<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub;

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
     * @param mixed $inventoryStatus
     */
    public function setInventoryStatus($inventoryStatus)
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
