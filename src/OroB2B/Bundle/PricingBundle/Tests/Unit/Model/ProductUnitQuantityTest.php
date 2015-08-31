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
        $instance = new ProductUnitQuantity($this->getProduct(42), (new ProductUnit())->setCode('kg'), $quantity);

        $this->assertInstanceOf('OroB2B\Bundle\PricingBundle\Model\ProductUnitQuantity', $instance);
        $this->assertEquals($this->getProduct(42), $instance->getProduct());
        $this->assertEquals((new ProductUnit())->setCode('kg'), $instance->getProductUnit());
        $this->assertEquals($quantity, $instance->getQuantity());
    }

    /**
     * @return array
     */
    public function productUnitQuantityDataProvider()
    {
        return [
            [0],
            ['0'],
            [1],
            [1.1],
            ['1'],
            ['1.1']
        ];
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Product must have id.
     */
    public function testConstructorProductException()
    {
        new ProductUnitQuantity(new Product(), (new ProductUnit())->setCode('kg'), 1);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ProductUnit must have code.
     */
    public function testConstructorProductUnitException()
    {
        new ProductUnitQuantity($this->getProduct(42), new ProductUnit(), 1);
    }

    /**
     * @dataProvider constructorExceptionDataProvider
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Quantity must be numeric and more than or equal zero.
     *
     * @param mixed $quantity
     */
    public function testConstructorQuantityException($quantity)
    {
        new ProductUnitQuantity($this->getProduct(42), (new ProductUnit())->setCode('kg'), $quantity);
    }

    /**
     * @return array
     */
    public function constructorExceptionDataProvider()
    {
        return [
            [-1],
            [''],
            [null],
            ['1a']
        ];
    }

    public function testGetIdentifier()
    {
        $product = $this->getProduct(150);

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $productUnitQuantity = new ProductUnitQuantity($product, $productUnit, 42);

        $this->assertEquals('150-kg-42', $productUnitQuantity->getIdentifier());
    }

    /**
     * @param $id
     * @return Product
     */
    protected function getProduct($id)
    {
        $product = new Product();

        $reflection = new \ReflectionProperty(get_class($product), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($product, $id);

        return $product;
    }
}
