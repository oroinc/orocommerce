<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

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
