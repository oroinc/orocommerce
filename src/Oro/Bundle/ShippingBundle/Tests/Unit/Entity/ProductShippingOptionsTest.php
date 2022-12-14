<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductShippingOptionsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductShippingOptions */
    private $entity;

    protected function setUp(): void
    {
        $this->entity = new ProductShippingOptions();
    }

    private function getDimensionsLength(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'dimensionsLength');
    }

    private function getDimensionsWidth(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'dimensionsWidth');
    }

    private function getDimensionsHeight(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'dimensionsHeight');
    }

    private function getDimensionsUnit(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'dimensionsUnit');
    }

    private function getWeightValue(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'weightValue');
    }

    private function getWeightUnit(ProductShippingOptions $entity): mixed
    {
        return ReflectionUtil::getPropertyValue($entity, 'weightUnit');
    }

    public function testGettersAndSetters()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['productUnit', new ProductUnit()],
            ['weight', new Weight()],
            ['dimensions', new Dimensions()],
            ['freightClass', new FreightClass()],
        ];

        self::assertPropertyAccessors($this->entity, $properties);
    }

    public function testGetEntityIdentifier()
    {
        self::assertSame($this->entity->getId(), $this->entity->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        self::assertSame($this->entity, $this->entity->getProductHolder());
    }

    public function testGetProductUnitCode()
    {
        $this->entity->setProductUnit((new ProductUnit())->setCode('code'));

        self::assertSame('code', $this->entity->getProductUnitCode());
    }

    public function testGetProductSku()
    {
        $this->entity->setProduct((new Product())->setSku('sku'));

        self::assertSame('sku', $this->entity->getProductSku());
    }

    public function testSetGetWeight()
    {
        self::assertNull($this->entity->getWeight());

        $this->entity->updateWeight();
        $this->entity->loadWeight();
        self::assertNull($this->entity->getWeight()->getValue());
        self::assertNull($this->entity->getWeight()->getUnit());

        $value = 11.1;
        $unit = new WeightUnit();

        ReflectionUtil::setPropertyValue($this->entity, 'weightValue', $value);
        ReflectionUtil::setPropertyValue($this->entity, 'weightUnit', $unit);
        $this->entity->loadWeight();

        $weight = $this->entity->getWeight();
        self::assertInstanceOf(Weight::class, $weight);
        self::assertEquals($value, $weight->getValue());
        self::assertSame($unit, $weight->getUnit());

        $weight = Weight::create(42.2, (new WeightUnit())->setCode('lbs'));
        $this->entity->setWeight($weight);
        self::assertSame($weight, $this->entity->getWeight());

        $this->entity->updateWeight();
        self::assertEquals($weight->getValue(), $this->getWeightValue($this->entity));
        self::assertEquals($weight->getUnit(), $this->getWeightUnit($this->entity));

        $this->entity->setWeight(null);
        $this->entity->updateWeight();
        self::assertNull($this->getWeightValue($this->entity));
        self::assertNull($this->getWeightUnit($this->entity));
    }

    public function testSetGetDimensions()
    {
        self::assertNull($this->entity->getDimensions());

        $this->entity->updateDimensions();
        self::assertNull($this->getDimensionsLength($this->entity));
        self::assertNull($this->getDimensionsWidth($this->entity));
        self::assertNull($this->getDimensionsHeight($this->entity));
        self::assertNull($this->getDimensionsUnit($this->entity));

        $length = 12.3;
        $width = 45.6;
        $height = 78.9;
        $unit = new LengthUnit();

        ReflectionUtil::setPropertyValue($this->entity, 'dimensionsLength', $length);
        ReflectionUtil::setPropertyValue($this->entity, 'dimensionsWidth', $width);
        ReflectionUtil::setPropertyValue($this->entity, 'dimensionsHeight', $height);
        ReflectionUtil::setPropertyValue($this->entity, 'dimensionsUnit', $unit);
        $this->entity->loadDimensions();

        $dimensions = $this->entity->getDimensions();
        self::assertInstanceOf(Dimensions::class, $dimensions);
        self::assertEquals($length, $dimensions->getValue()->getLength());
        self::assertEquals($width, $dimensions->getValue()->getWidth());
        self::assertEquals($height, $dimensions->getValue()->getHeight());
        self::assertSame($unit, $dimensions->getUnit());

        $dimensions = Dimensions::create(32.1, 65.4, 98.7, (new LengthUnit())->setCode('inch'));
        $this->entity->setDimensions($dimensions);
        self::assertSame($dimensions, $this->entity->getDimensions());

        $this->entity->updateDimensions();
        self::assertEquals($dimensions->getValue()->getLength(), $this->getDimensionsLength($this->entity));
        self::assertEquals($dimensions->getValue()->getWidth(), $this->getDimensionsWidth($this->entity));
        self::assertEquals($dimensions->getValue()->getHeight(), $this->getDimensionsHeight($this->entity));
        self::assertEquals($dimensions->getUnit(), $this->getDimensionsUnit($this->entity));

        $this->entity->setDimensions(null);
        self::assertNull($this->getDimensionsLength($this->entity));
        self::assertNull($this->getDimensionsWidth($this->entity));
        self::assertNull($this->getDimensionsHeight($this->entity));
        self::assertNull($this->getDimensionsUnit($this->entity));
    }
}
