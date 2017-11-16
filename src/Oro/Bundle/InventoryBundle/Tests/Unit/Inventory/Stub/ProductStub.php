<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
