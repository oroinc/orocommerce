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
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ShippingPackagesByLineItemBuilderTest extends TestCase
{
    /**
     * @var ShippingPackageOptionsFactory
     */
    private $packageOptionsFactory;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var ShippingPackagesByLineItemBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->packageOptionsFactory = new ShippingPackageOptionsFactory();
        $this->expressionLanguage = new ExpressionLanguage();

        $this->builder = new ShippingPackagesByLineItemBuilder(
            $this->packageOptionsFactory,
            $this->expressionLanguage
        );
    }

    public function testAddItemTooBigWeight()
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 1, 1),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(20)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigLength()
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15 and length < 10');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(11, 1, 1),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(5)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigWidth()
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15 and length < 10 and width < 10');
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 11, 3),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(5)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigHeight()
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 15 and length < 10 and width < 10 and height < 10'
        );
        $lineItem = new ShippingLineItem([
            ShippingLineItem::FIELD_DIMENSIONS => Dimensions::create(1, 1, 11),
            ShippingLineItem::FIELD_WEIGHT => Weight::create(5)
        ]);

        $this->builder->init($packageSettings);
        static::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddLineItemDividedByWeightOnlyWhenDimensionsIgnored()
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 10 and length < 20 and (length + 2*width + 2*height < 20)',
            true
        );
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
                    Dimensions::create(0, 0, 0, null),
                    Weight::create(8, (new WeightUnit())->setCode('kg'))
                ),
                new ShippingPackageOptions(
                    Dimensions::create(0, 0, 0, null),
                    Weight::create(4, (new WeightUnit())->setCode('kg'))
                )
            ],
            $this->builder->getResult()
        );
    }

    public function testAddLineItemDividedByWeight()
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 10 and length < 20 and (length + 2*width + 2*height < 20)'
        );
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
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight <= 10 and length <= 20 and (length + 2*width + 2*height <= 20)'
        );
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
