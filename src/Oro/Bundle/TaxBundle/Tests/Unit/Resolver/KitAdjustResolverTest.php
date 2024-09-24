<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\KitAdjustResolver;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class KitAdjustResolverTest extends TestCase
{
    private TaxationSettingsProvider|MockObject $settingsProvider;

    private KitAdjustResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->resolver = new KitAdjustResolver(
            $this->settingsProvider,
            new RoundingResolver()
        );
    }

    public function testResolveWithoutItems(): void
    {
        $result = new Result();
        $result->offsetSet(Result::UNIT, ResultElement::create(10, 9, 1, 0));
        $taxable = (new Taxable())->setResult(clone $result);

        $this->resolver->resolve($taxable);
        $this->assertEquals($result, $taxable->getResult());

        $taxable->setKitTaxable(true);

        $this->resolver->resolve($taxable);
        $this->assertEquals($result, $taxable->getResult());
    }

    public function testResolveWithItems(): void
    {
        $result = new Result();
        $resultItem = new Result();
        $result->offsetSet(Result::UNIT, ResultElement::create(10, 9, 1, 0));
        $resultItem->offsetSet(Result::UNIT, ResultElement::create(3, 2, 1, 0));
        $taxableItem = (new Taxable())->setResult(clone $resultItem);

        $taxable = new Taxable();
        $taxable->setResult(clone $result);
        $taxable->addItem($taxableItem);

        $this->resolver->resolve($taxable);
        $this->assertEquals($result, $taxable->getResult());
    }

    public function testResolveKitTaxable(): void
    {
        $row = ResultElement::create(3440, 3200, 240, '0.00');
        $row->setDiscountsIncluded(true);

        $expected = new Result([
            Result::UNIT => ResultElement::create(1720, 1600, 120, '0.00'),
            Result::ROW => $row,
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2400', '144'),
                TaxResultElement::create('region', '0.04', '2400', '96')
            ]
        ]);

        $taxableItem = new Taxable();
        $taxableItem->setQuantity(2);
        $taxableItem->setPrice(100);
        $taxableItem->setResult(new Result([
            Result::UNIT => ResultElement::create(110, 100, 10, '0.00'),
            Result::ROW => ResultElement::create(220, 200, 20, '0.00'),
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '200', '12'),
                TaxResultElement::create('region', '0.04', '200', '8')
            ]
        ]));

        $taxableItem2 = new Taxable();
        $taxableItem2->setQuantity(2);
        $taxableItem2->setPrice(200);
        $taxableItem2->setResult(new Result([
            Result::UNIT => ResultElement::create(200, 200, 0, '0.00'),
            Result::ROW => ResultElement::create(400, 400, 0, '0.00')
        ]));

        $taxable = new Taxable();
        $taxable->setKitTaxable(true);
        $taxable->setQuantity(2);
        $taxable->setPrice(1000);
        $taxable->addItem($taxableItem);
        $taxable->addItem($taxableItem2);
        $taxable->setResult(new Result([
            Result::UNIT => ResultElement::create(1100, 1000, 100, '0.00'),
            Result::ROW => (ResultElement::create(2200, 2000, 200, '0.00'))->setDiscountsIncluded(true),
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2000', '120'),
                TaxResultElement::create('region', '0.04', '2000', '80')
            ]
        ]));

        $this->resolver->resolve($taxable);

        $this->assertEquals($expected, $taxable->getResult());
    }

    public function testResolveKitTaxableAndWithoutUnitAndRow(): void
    {
        $row = ResultElement::create(3000, 2800, 200, '0.00');

        $expected = new Result([
            Result::UNIT => ResultElement::create(1700, 1600, 100, '0.00'),
            Result::ROW => $row,
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2000', '120'),
                TaxResultElement::create('region', '0.04', '2000', '80')
            ]
        ]);

        $taxableItem = new Taxable();
        $taxableItem->setQuantity(2);
        $taxableItem->setPrice(100);
        $taxableItem->setResult(new Result([
            Result::ROW => ResultElement::create(200, 200, 0, '0.00'),
        ]));

        $taxableItem2 = new Taxable();
        $taxableItem2->setQuantity(2);
        $taxableItem2->setPrice(200);
        $taxableItem2->setResult(new Result([
            Result::UNIT => ResultElement::create(200, 200, 0, '0.00'),
        ]));

        $taxable = new Taxable();
        $taxable->setKitTaxable(true);
        $taxable->setQuantity(2);
        $taxable->setPrice(1000);
        $taxable->addItem($taxableItem);
        $taxable->addItem($taxableItem2);
        $taxable->setResult(new Result([
            Result::UNIT => ResultElement::create(1100, 1000, 100, '0.00'),
            Result::ROW => ResultElement::create(2200, 2000, 200, '0.00'),
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2000', '120'),
                TaxResultElement::create('region', '0.04', '2000', '80')
            ]
        ]));

        $this->resolver->resolve($taxable);

        $this->assertEquals($expected, $taxable->getResult());
    }

    public function testResolveKitTaxableAndIsStartCalculationOnItem(): void
    {
        $this->settingsProvider->expects(self::exactly(2))
            ->method('isStartCalculationOnItem')
            ->willReturn(true);

        $row = ResultElement::create('3091.9770', '2879.98', '211.9970', '-0.0030');

        $expected = new Result([
            Result::UNIT => ResultElement::create('1545.9885', '1439.99', '105.9985', '-0.0015'),
            Result::ROW => $row,
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2000', '120'),
                TaxResultElement::create('region', '0.04', '2000', '80'),
                TaxResultElement::create('city2', '0.08', '79.98', '6.4'),
                TaxResultElement::create('region2', '0.07', '79.98', '5.6')
            ]
        ]);

        $taxableItem = new Taxable();
        $taxableItem->setQuantity(1);
        $taxableItem->setPrice(39.99);
        $taxableItem->setResult(new Result([
            Result::UNIT => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
            Result::ROW => ResultElement::create('45.9885', '39.99', '5.9985', '-0.0015'),
            Result::TAXES => [
                TaxResultElement::create('city2', '0.08', '39.99', '3.199200'),
                TaxResultElement::create('region2', '0.07', '39.99', '2.799300')
            ]
        ]));

        $taxableItem2 = new Taxable();
        $taxableItem2->setQuantity(2);
        $taxableItem2->setPrice(200);
        $taxableItem2->setResult(new Result([
            Result::UNIT => ResultElement::create(200, 200, 0, '0.00'),
            Result::ROW => ResultElement::create(400, 400, 0, '0.00')
        ]));

        $taxable = new Taxable();
        $taxable->setKitTaxable(true);
        $taxable->setQuantity(2);
        $taxable->setPrice(1000);
        $taxable->addItem($taxableItem);
        $taxable->addItem($taxableItem2);
        $taxable->setResult(new Result([
            Result::UNIT => ResultElement::create(1100, 1000, 100, '0.00'),
            Result::ROW => ResultElement::create(2200, 2000, 200, '0.00'),
            Result::TAXES => [
                TaxResultElement::create('city', '0.06', '2000', '120'),
                TaxResultElement::create('region', '0.04', '2000', '80')
            ]
        ]));

        $this->resolver->resolve($taxable);

        $this->assertEquals($expected, $taxable->getResult());
    }
}
