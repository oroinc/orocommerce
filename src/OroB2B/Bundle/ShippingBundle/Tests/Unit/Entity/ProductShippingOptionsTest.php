<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Model\DimensionsValue;
use OroB2B\Bundle\ShippingBundle\Model\Weight;

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
            ['productUnit', new ProductUnit()],
            ['weight', new Weight()],
            ['dimensions', new Dimensions()],
            ['freightClass', new FreightClass()],
        ];

        $this->assertPropertyAccessors($this->entity, $properties);
    }

    public function testGetEntityIdentifier()
    {
        $this->assertNull($this->entity->getEntityIdentifier());

        $this->setProperty($this->entity, 'id', 123);

        $this->assertSame(123, $this->entity->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        $this->assertSame($this->entity, $this->entity->getProductHolder());
    }

    public function testGetProductUnitCode()
    {
        $this->entity->setProductUnit((new ProductUnit)->setCode('code'));

        $this->assertSame('code', $this->entity->getProductUnitCode());
    }

    public function testGetProductSku()
    {
        $this->entity->setProduct((new Product)->setSku('sku'));

        $this->assertSame('sku', $this->entity->getProductSku());
    }

    public function testSetGetWeight()
    {
        $this->assertNull($this->entity->getWeight());

        $this->entity->updateWeight();
        $this->assertAttributeEquals(null, 'weightValue', $this->entity);
        $this->assertAttributeEquals(null, 'weightUnit', $this->entity);

        $value = 11.1;
        $unit = new WeightUnit();

        $this->setProperty($this->entity, 'weightValue', $value);
        $this->setProperty($this->entity, 'weightUnit', $unit);
        $this->entity->loadWeight();

        $weight = $this->entity->getWeight();
        $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Model\Weight', $weight);
        $this->assertEquals($value, $weight->getValue());
        $this->assertSame($unit, $weight->getUnit());

        $weight = Weight::create(42.2, new WeightUnit('lbs'));
        $this->entity->setWeight($weight);
        $this->assertSame($weight, $this->entity->getWeight());

        $this->entity->updateWeight();
        $this->assertAttributeEquals($weight->getValue(), 'weightValue', $this->entity);
        $this->assertAttributeEquals($weight->getUnit(), 'weightUnit', $this->entity);

        $this->setProperty($this->entity, 'weight', null);
        $this->entity->updateWeight();
        $this->assertAttributeEquals(null, 'weightValue', $this->entity);
        $this->assertAttributeEquals(null, 'weightUnit', $this->entity);
    }

    public function testSetGetDimensions()
    {
        $this->assertNull($this->entity->getDimensions());

        $this->entity->updateDimensions();
        $this->assertAttributeEquals(null, 'dimensionsLength', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsWidth', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsHeight', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsUnit', $this->entity);

        $length = 12.3;
        $width = 45.6;
        $height = 78.9;
        $unit = new LengthUnit();

        $this->setProperty($this->entity, 'dimensionsLength', $length);
        $this->setProperty($this->entity, 'dimensionsWidth', $width);
        $this->setProperty($this->entity, 'dimensionsHeight', $height);
        $this->setProperty($this->entity, 'dimensionsUnit', $unit);
        $this->entity->loadDimensions();

        $dimensions = $this->entity->getDimensions();
        $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Model\Dimensions', $dimensions);
        $this->assertEquals($length, $dimensions->getValue()->getLength());
        $this->assertEquals($width, $dimensions->getValue()->getWidth());
        $this->assertEquals($height, $dimensions->getValue()->getHeight());
        $this->assertSame($unit, $dimensions->getUnit());

        $dimensions = Dimensions::create(32.1, 65.4, 98.7, new LengthUnit('inch'));
        $this->entity->setDimensions($dimensions);
        $this->assertSame($dimensions, $this->entity->getDimensions());

        $this->entity->updateDimensions();
        $this->assertAttributeEquals($dimensions->getValue()->getLength(), 'dimensionsLength', $this->entity);
        $this->assertAttributeEquals($dimensions->getValue()->getWidth(), 'dimensionsWidth', $this->entity);
        $this->assertAttributeEquals($dimensions->getValue()->getHeight(), 'dimensionsHeight', $this->entity);
        $this->assertAttributeEquals($dimensions->getUnit(), 'dimensionsUnit', $this->entity);

        $this->setProperty($this->entity, 'dimensions', null);
        $this->entity->updateDimensions();
        $this->assertAttributeEquals(null, 'dimensionsLength', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsWidth', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsHeight', $this->entity);
        $this->assertAttributeEquals(null, 'dimensionsUnit', $this->entity);
    }

    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
