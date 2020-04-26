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
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductShippingOptionsTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var ProductShippingOptions $entity */
    protected $entity;

    protected function setUp(): void
    {
        $this->entity = new class() extends ProductShippingOptions {
            public function xgetDimensionsLength(): ?float
            {
                return $this->dimensionsLength;
            }

            public function xgetDimensionsWidth(): ?float
            {
                return $this->dimensionsWidth;
            }

            public function xgetDimensionsHeight(): ?float
            {
                return $this->dimensionsHeight;
            }

            public function xgetDimensionsUnit(): ?LengthUnit
            {
                return $this->dimensionsUnit;
            }

            public function xsetDimensionsLengthWidthHeightUnit(
                float $dimensionsLength,
                float $dimensionsWidth,
                float $dimensionsHeight,
                LengthUnit $dimensionsUnit
            ): void {
                $this->dimensionsLength = $dimensionsLength;
                $this->dimensionsWidth = $dimensionsWidth;
                $this->dimensionsHeight = $dimensionsHeight;
                $this->dimensionsUnit = $dimensionsUnit;
            }

            public function xgetWeightValue(): ?float
            {
                return $this->weightValue;
            }

            public function xgetWeightUnit(): ?WeightUnit
            {
                return $this->weightUnit;
            }

            public function xsetWeightValueUnit(float $weightValue, WeightUnit $weightUnit): void
            {
                $this->weightValue = $weightValue;
                $this->weightUnit = $weightUnit;
            }
        };
    }

    protected function tearDown(): void
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

        static::assertPropertyAccessors($this->entity, $properties);
    }

    public function testGetEntityIdentifier()
    {
        static::assertSame($this->entity->getId(), $this->entity->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        static::assertSame($this->entity, $this->entity->getProductHolder());
    }

    public function testGetProductUnitCode()
    {
        $this->entity->setProductUnit((new ProductUnit())->setCode('code'));

        static::assertSame('code', $this->entity->getProductUnitCode());
    }

    public function testGetProductSku()
    {
        $this->entity->setProduct((new Product())->setSku('sku'));

        static::assertSame('sku', $this->entity->getProductSku());
    }

    public function testSetGetWeight()
    {
        static::assertNull($this->entity->getWeight());

        $this->entity->updateWeight();
        $this->entity->loadWeight();
        static::assertNull($this->entity->getWeight()->getValue());
        static::assertNull($this->entity->getWeight()->getUnit());

        $value = 11.1;
        $unit = new WeightUnit();

        $this->entity->xsetWeightValueUnit($value, $unit);
        $this->entity->loadWeight();

        $weight = $this->entity->getWeight();
        static::assertInstanceOf(Weight::class, $weight);
        static::assertEquals($value, $weight->getValue());
        static::assertSame($unit, $weight->getUnit());

        $weight = Weight::create(42.2, (new WeightUnit())->setCode('lbs'));
        $this->entity->setWeight($weight);
        static::assertSame($weight, $this->entity->getWeight());

        $this->entity->updateWeight();
        static::assertEquals($weight->getValue(), $this->entity->xgetWeightValue());
        static::assertEquals($weight->getUnit(), $this->entity->xgetWeightUnit());

        $this->entity->setWeight(null);
        $this->entity->updateWeight();
        static::assertNull($this->entity->xgetWeightValue());
        static::assertNull($this->entity->xgetWeightUnit());
    }

    public function testSetGetDimensions()
    {
        static::assertNull($this->entity->getDimensions());

        $this->entity->updateDimensions();
        static::assertNull($this->entity->xgetDimensionsLength());
        static::assertNull($this->entity->xgetDimensionsWidth());
        static::assertNull($this->entity->xgetDimensionsHeight());
        static::assertNull($this->entity->xgetDimensionsUnit());

        $length = 12.3;
        $width = 45.6;
        $height = 78.9;
        $unit = new LengthUnit();

        $this->entity->xsetDimensionsLengthWidthHeightUnit($length, $width, $height, $unit);
        $this->entity->loadDimensions();

        $dimensions = $this->entity->getDimensions();
        static::assertInstanceOf(Dimensions::class, $dimensions);
        static::assertEquals($length, $dimensions->getValue()->getLength());
        static::assertEquals($width, $dimensions->getValue()->getWidth());
        static::assertEquals($height, $dimensions->getValue()->getHeight());
        static::assertSame($unit, $dimensions->getUnit());

        $dimensions = Dimensions::create(32.1, 65.4, 98.7, (new LengthUnit())->setCode('inch'));
        $this->entity->setDimensions($dimensions);
        static::assertSame($dimensions, $this->entity->getDimensions());

        $this->entity->updateDimensions();
        static::assertEquals($dimensions->getValue()->getLength(), $this->entity->xgetDimensionsLength());
        static::assertEquals($dimensions->getValue()->getWidth(), $this->entity->xgetDimensionsWidth());
        static::assertEquals($dimensions->getValue()->getHeight(), $this->entity->xgetDimensionsHeight());
        static::assertEquals($dimensions->getUnit(), $this->entity->xgetDimensionsUnit());

        $this->entity->setDimensions(null);
        static::assertNull($this->entity->xgetDimensionsLength());
        static::assertNull($this->entity->xgetDimensionsWidth());
        static::assertNull($this->entity->xgetDimensionsHeight());
        static::assertNull($this->entity->xgetDimensionsUnit());
    }
}
