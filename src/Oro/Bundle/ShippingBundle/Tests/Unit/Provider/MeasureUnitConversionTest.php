<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class MeasureUnitConversionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Dimensions */
    private $dimensionsUnit;

    /** @var Weight */
    private $weightUnit;

    protected function setUp(): void
    {
        $this->dimensionsUnit = Dimensions::create(
            32.1,
            65.4,
            98.7,
            (new LengthUnit)->setCode('cm')->setConversionRates([
                'inch' => 0.393701,
                'foot' => 0.0328084,
                'm'    => 0.01
            ])
        );
        $this->weightUnit = Weight::create(
            52.12,
            (new WeightUnit())->setCode('kg')->setConversionRates([
                'lbs' => 2.20462262
            ])
        );
    }

    private function getMeasureUnitConversion(array $units = null): MeasureUnitConversion
    {
        $configManager = $this->createMock(ConfigManager::class);
        if (null !== $units && !$units) {
            $configManager->expects(self::never())
                ->method('get');
        } else {
            $configManager->expects(self::any())
                ->method('get')
                ->willReturn($units ?? ['inch', 'foot', 'cm', 'm', 'lbs', 'kg']);
        }

        return new MeasureUnitConversion($configManager, Dimensions::class, Weight::class);
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(Dimensions|Weight $unit, string $shippingUnit)
    {
        $measureUnitConversion = $this->getMeasureUnitConversion();
        /** @var Dimensions $convertedUnit */
        $convertedUnit = $measureUnitConversion->convert($unit, $shippingUnit);

        self::assertContains(get_class($convertedUnit), [Dimensions::class, Weight::class]);
        self::assertEquals($shippingUnit, $convertedUnit->getUnit()->getCode());
    }

    public function convertDataProvider(): array
    {
        return [
            'dimensions unit' => [
                'unit' => Dimensions::create(
                    32.1,
                    65.4,
                    98.7,
                    (new LengthUnit)->setCode('cm')->setConversionRates([
                        'inch' => 0.393701,
                        'foot' => 0.0328084,
                        'm'    => 0.01
                    ])
                ),
                'shippingUnit' => 'inch'
            ],
            'weight unit' => [
                'unit' => Weight::create(
                    52.12,
                    (new WeightUnit())->setCode('kg')->setConversionRates([
                        'lbs' => 2.20462262
                    ])
                ),
                'shippingUnit' => 'lbs'
            ]
        ];
    }

    public function testConvertDimensions()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion();
        /** @var Dimensions $convertedUnit */
        $convertedUnit = $measureUnitConversion->convertDimensions($this->dimensionsUnit, 'inch');

        self::assertInstanceOf(Dimensions::class, $convertedUnit);
        self::assertEqualsWithDelta(12.6378021, $convertedUnit->getValue()->getLength(), 0.0000001);
        self::assertEqualsWithDelta(25.7480454, $convertedUnit->getValue()->getWidth(), 0.0000001);
        self::assertEqualsWithDelta(38.8582887, $convertedUnit->getValue()->getHeight(), 0.0000001);
        self::assertEquals('inch', $convertedUnit->getUnit()->getCode());
    }

    public function testConvertWeight()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion();
        /** @var Weight $convertedUnit */
        $convertedUnit = $measureUnitConversion->convertWeight($this->weightUnit, 'lbs');

        self::assertInstanceOf(Weight::class, $convertedUnit);
        self::assertSame(114.90493095439999, $convertedUnit->getValue());
        self::assertEquals('lbs', $convertedUnit->getUnit()->getCode());
    }

    public function testIsDimensionsEnabled()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion();
        self::assertTrue($measureUnitConversion->isDimensionsEnabled($this->dimensionsUnit));
    }

    public function testIsDimensionsWithEmptyUnit()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion([]);
        self::assertFalse($measureUnitConversion->isDimensionsEnabled(new Dimensions()));
    }

    public function testIsDimensionsDisabled()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion(['inch', 'foot', 'm', 'lbs', 'kg']);
        self::assertFalse($measureUnitConversion->isDimensionsEnabled($this->dimensionsUnit));
    }

    public function testIsWeightEnabled()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion();
        self::assertTrue($measureUnitConversion->isWeightEnabled($this->weightUnit));
    }

    public function testIsWeightWithEmptyUnit()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion([]);
        self::assertFalse($measureUnitConversion->isWeightEnabled(new Weight()));
    }

    public function testIsWeightDisabled()
    {
        $measureUnitConversion = $this->getMeasureUnitConversion(['inch', 'foot', 'cm', 'm', 'lbs']);
        self::assertFalse($measureUnitConversion->isWeightEnabled($this->weightUnit));
    }
}
