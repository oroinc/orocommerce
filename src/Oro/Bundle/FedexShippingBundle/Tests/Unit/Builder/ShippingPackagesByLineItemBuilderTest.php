<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Builder;

use Oro\Bundle\FedexShippingBundle\Builder\ShippingPackagesByLineItemBuilder;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettings;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Factory\ShippingPackageOptionsFactory;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\ShippingPackageOptions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ShippingPackagesByLineItemBuilderTest extends TestCase
{
    use ShippingLineItemTrait;

    private ShippingPackagesByLineItemBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new ShippingPackagesByLineItemBuilder(
            new ShippingPackageOptionsFactory(),
            new ExpressionLanguage()
        );
    }

    public function testAddItemTooBigWeight(): void
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15');
        $lineItem = $this->getShippingLineItem()
            ->setDimensions(Dimensions::create(1, 1, 1))
            ->setWeight(Weight::create(20));

        $this->builder->init($packageSettings);
        self::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigLength(): void
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15 and length < 10');
        $lineItem = $this->getShippingLineItem()
            ->setDimensions(Dimensions::create(11, 1, 1))
            ->setWeight(Weight::create(5));

        $this->builder->init($packageSettings);
        self::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigWidth(): void
    {
        $packageSettings = new FedexPackageSettings('kg', 'cm', 'weight < 15 and length < 10 and width < 10');
        $lineItem = $this->getShippingLineItem()
            ->setDimensions(Dimensions::create(1, 11, 3))
            ->setWeight(Weight::create(5));

        $this->builder->init($packageSettings);
        self::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddItemTooBigHeight(): void
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 15 and length < 10 and width < 10 and height < 10'
        );
        $lineItem = $this->getShippingLineItem()
            ->setDimensions(Dimensions::create(1, 1, 11))
            ->setWeight(Weight::create(5));

        $this->builder->init($packageSettings);
        self::assertFalse($this->builder->addLineItem($lineItem));
    }

    public function testAddLineItemDividedByWeightOnlyWhenDimensionsIgnored(): void
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 10 and length < 20 and (length + 2*width + 2*height < 20)',
            true
        );
        $lineItem = $this->getShippingLineItem(quantity: 3)
            ->setDimensions(Dimensions::create(1, 1, 1))
            ->setWeight(Weight::create(4));

        $this->builder->init($packageSettings);
        self::assertTrue($this->builder->addLineItem($lineItem));
        self::assertEquals(
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

    public function testAddLineItemDividedByWeight(): void
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight < 10 and length < 20 and (length + 2*width + 2*height < 20)'
        );
        $lineItem = $this->getShippingLineItem(quantity: 3)
            ->setDimensions(Dimensions::create(1, 1, 1))
            ->setWeight(Weight::create(4));

        $this->builder->init($packageSettings);
        self::assertTrue($this->builder->addLineItem($lineItem));
        self::assertEquals(
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

    public function testAddLineItemDividedByDimensions(): void
    {
        $packageSettings = new FedexPackageSettings(
            'kg',
            'cm',
            'weight <= 10 and length <= 20 and (length + 2*width + 2*height <= 20)'
        );
        $lineItem = $this->getShippingLineItem(quantity: 3)
            ->setDimensions(Dimensions::create(2, 2, 2))
            ->setWeight(Weight::create(1));

        $this->builder->init($packageSettings);
        self::assertTrue($this->builder->addLineItem($lineItem));
        self::assertEquals(
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

    public function testBuilderReused(): void
    {
        $this->testAddLineItemDividedByWeight();
        $this->testAddLineItemDividedByDimensions();
    }
}
