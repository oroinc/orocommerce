<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;

class FreightClassTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var  FreightClass $entity */
    protected $entity;

    public function setUp()
    {
        $this->entity = new FreightClass();
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

        $this->assertPropertyAccessors(new FreightClass(), $properties);
    }
}
