<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Model;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;

class CategoryUnitPrecisionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['unit', new ProductUnit()],
            ['precision', 2],
        ];
        $this->assertPropertyAccessors(new CategoryUnitPrecision(), $properties);
    }
}
