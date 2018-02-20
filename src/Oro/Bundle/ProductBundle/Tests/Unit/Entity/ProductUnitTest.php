<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTestCase;

class ProductUnitTest extends EntityTestCase
{
    public function testProperties()
    {
        $properties = [
            ['code', 'kg'],
            ['defaultPrecision', 3],
        ];

        $this->assertPropertyAccessors(new ProductUnit(), $properties);
    }
}
