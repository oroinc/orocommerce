<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductUnitTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['code', 'kg'],
            ['defaultPrecision', 3],
        ];

        $this->assertPropertyAccessors(new ProductUnit(), $properties);
    }
}
