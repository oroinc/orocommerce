<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

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
