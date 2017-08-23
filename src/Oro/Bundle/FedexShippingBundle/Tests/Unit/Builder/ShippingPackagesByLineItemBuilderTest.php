<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Builder;

use Oro\Bundle\FedexShippingBundle\Builder\ShippingPackagesByLineItemBuilder;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Factory\ShippingPackageOptionsFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use PHPUnit\Framework\TestCase;

class ShippingPackagesByLineItemBuilderTest extends TestCase
{
    /**
     * @var ShippingPackageOptionsFactory
     */
    private $packageOptionsFactory;

    /**
     * @var ShippingPackagesByLineItemBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->packageOptionsFactory = new ShippingPackageOptionsFactory();
        $this->builder = new ShippingPackagesByLineItemBuilder($this->packageOptionsFactory);
    }

    public function testAddItemTooBigWeight()
    {
        $packageSettings = new FedexPackageSettings(10, 10, 10, 'kg', 'cm');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 1, 1),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(20)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigLength()
    {
        $packageSettings = new FedexPackageSettings(10, 10, 10, 'kg', 'cm');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(11, 1, 1),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(5)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigGirth()
    {
        $packageSettings = new FedexPackageSettings(10, 10, 10, 'kg', 'cm');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 2, 3),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(5)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddLineItemDividedByWeight()
    {
        $packageSettings = new FedexPackageSettings(10, 20, 20, 'kg', 'cm');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 1, 1),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(4),
            ShippingLineItem::FIELD_QUANTITY => 3,
        ]);

        $this->builder->init($packageSettings);
        static::assertTrue($this->builder->addLineItem($lineItem));
        static::assertEquals(
            [
                new ShippingPackageOptions(
                    Dimensions::create(2, 2, 2, (new LengthUnit())->setCode('cm')),
                    Weight::create(8, (new WeightUnit())->setCode('kg'))
                ),
                new ShippingPackageOptions(
                    Dimensions::create(1, 1, 1, (new LengthUnit())->setCode('cm')),
                    Weight::create(4, (new WeightUnit())->setCode('kg'))
                )
            ],
            $this->builder->getResult()
        );
    }

    public function testAddLineItemDividedByDimensions()
    {
        $packageSettings = new FedexPackageSettings(10, 20, 20, 'kg', 'cm');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(2, 2, 2),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(1),
            ShippingLineItem::FIELD_QUANTITY => 3,
        ]);

        $this->builder->init($packageSettings);
        static::assertTrue($this->builder->addLineItem($lineItem));
        static::assertEquals(
            [
                new ShippingPackageOptions(
                    Dimensions::create(4, 4, 4, (new LengthUnit())->setCode('cm')),
                    Weight::create(2, (new WeightUnit())->setCode('kg'))
                ),
                new ShippingPackageOptions(
                    Dimensions::create(2, 2, 2, (new LengthUnit())->setCode('cm')),
                    Weight::create(1, (new WeightUnit())->setCode('kg'))
                )
            ],
            $this->builder->getResult()
        );
    }

    public function testBuilderReused()
    {
        $this->testAddLineItemDividedByWeight();
        $this->testAddLineItemDividedByDimensions();
    }
}
