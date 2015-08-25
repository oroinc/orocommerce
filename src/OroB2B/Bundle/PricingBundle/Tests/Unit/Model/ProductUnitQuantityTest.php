<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Model;

use OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class ProductUnitQuantityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider productUnitQuantityDataProvider
     *
     * @param mixed $quantity
     */
    public function testProductUnitQuantity($quantity)
    {
        $instance = new ProductUnitQuantity(new Product(), new ProductUnit(), $quantity);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity', $instance);
        $this->assertEquals(new Product(), $instance->getProduct());
        $this->assertEquals(new ProductUnit(), $instance->getProductUnit());
        $this->assertEquals($quantity, $instance->getQuantity());
    }

    /**
     * @return array
     */
    public function productUnitQuantityDataProvider()
    {
        return [
            [1],
            [1.1],
            ['1'],
            ['1.1']
        ];
    }

    /**
     * @dataProvider constructorExceptionDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Quantity must be positive float or integer.
     *
     * @param mixed $quantity
     */
    public function testConstructorException($quantity)
    {
        new ProductUnitQuantity(new Product(), new ProductUnit(), $quantity);
    }

    /**
     * @return array
     */
    public function constructorExceptionDataProvider()
    {
        return [
            [0],
            [''],
            [null],
            ['1a']
        ];
    }
}
