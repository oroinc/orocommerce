<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;

class LengthUnitTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var LengthUnit $entity */
    protected $entity;

    public function setUp()
    {
        $this->entity = new LengthUnit();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testAccessors()
    {
        $properties = [
            ['code', '123'],
            ['conversionRates', ['rate1' => 'rateValue']],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testToString()
    {
        $this->entity->setCode('test');

        $this->assertEquals('test', (string)$this->entity);
    }
}
