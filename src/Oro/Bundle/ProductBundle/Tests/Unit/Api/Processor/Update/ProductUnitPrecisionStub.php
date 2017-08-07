<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Update;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionStub extends ProductUnitPrecision
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
