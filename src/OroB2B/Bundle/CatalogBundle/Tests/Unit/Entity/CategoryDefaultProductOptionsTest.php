<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\CatalogBundle\Entity\CategoryDefaultProductOptions;
use OroB2B\Bundle\CatalogBundle\Model\CategoryUnitPrecision;

class CategoryDefaultProductOptionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['unitPrecision', new CategoryUnitPrecision()]
        ];
        $this->assertPropertyAccessors(new CategoryDefaultProductOptions(), $properties);
    }
}
