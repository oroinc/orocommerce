<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;

class ProductShippingOptionsTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductShippingOptions $entity */
    protected $entity;

    public function setUp()
    {
        $this->entity = new ProductShippingOptions();
    }

    public function tearDown()
    {
        unset($this->entity);
    }

    public function testAccessors()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['unit', new ProductUnit()],
            ['weightUnit', new WeightUnit()],
            ['weight', '10.25'],
            ['lengthUnit', new LengthUnit()],
            ['length', '10.25'],
            ['width', '10.25'],
            ['height', '10.25'],
            ['freightClass', new FreightClass()],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }
}