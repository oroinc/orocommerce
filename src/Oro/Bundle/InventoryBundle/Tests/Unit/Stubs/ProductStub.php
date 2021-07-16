<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Stubs;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /** @var string|object|null */
    private $inventoryStatus;

    public function __construct(int $id)
    {
        $this->id = $id;

        parent::__construct();
    }

    /**
     * @return string|object|null
     */
    public function getInventoryStatus()
    {
        return $this->inventoryStatus;
    }

    /**
     * @param string|object|null $inventoryStatus
     */
    public function setInventoryStatus($inventoryStatus): void
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
