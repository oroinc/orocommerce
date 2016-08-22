<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitPrecisionTest extends EntityTestCase
{

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['product', new Product()],
            ['unit', new ProductUnit()],
            ['precision', 3],
            ['conversionRate', 2.5],
            ['sell', true]
        ];
        $this->assertPropertyAccessors(new ProductUnitPrecision(), $properties);
    }
}
