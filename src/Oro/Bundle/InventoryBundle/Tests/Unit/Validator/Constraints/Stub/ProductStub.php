<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Validator\Constraints\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
{
    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
