<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Stubs;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /** @var string */
    private $inventoryStatus;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;

        parent::__construct();
    }


    /**
     * @return string
     */
    public function getInventoryStatus(): string
    {
        return $this->inventoryStatus;
    }

    /**
     * @param string $inventoryStatus
     */
    public function setInventoryStatus(string $inventoryStatus): void
    {
        $this->inventoryStatus = $inventoryStatus;
    }
}
