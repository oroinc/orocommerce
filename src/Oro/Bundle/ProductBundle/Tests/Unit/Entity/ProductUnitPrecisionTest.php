<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductUnitPrecisionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

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
