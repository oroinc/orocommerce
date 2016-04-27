<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\ShippingBundle\Entity\DimensionUnit;

class DimensionUnitTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  DimensionUnit $entity */
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
        $date = new \DateTime();

        $properties = [
            ['id', 1],
            ['code', '123'],
            ['conversionRates', []],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $this->assertPropertyAccessors(new DimensionUnit(), $properties);
    }

    public function testConstruct()
    {
        $this->assertInternalType('array', $this->entity->getConversionRates());
        $this->assertEmpty($this->entity->getConversionRates());
    }
}
