<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionStub extends ProductUnitPrecision
{
    public function __construct(?int $id = null)
    {
        if ($id !== null) {
            $this->id = $id;
        }
    }
}
