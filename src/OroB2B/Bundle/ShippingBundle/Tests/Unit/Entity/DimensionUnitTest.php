<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\DimensionUnit;

class DimensionUnitTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var DimensionUnit $entity */
    protected $entity;

    public function setUp()
    {
        $this->entity = new DimensionUnit();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testAccessors()
    {
        $properties = [
            ['code', '123'],
            ['conversionRates', []],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }
}
