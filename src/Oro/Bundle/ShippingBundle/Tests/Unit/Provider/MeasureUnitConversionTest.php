<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;

class MeasureUnitConversionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $lengthConfigEntryName;

    /**
     * @var |\PHPUnit_Framework_MockObject_MockObject
     */
    protected $weightConfigEntryName;

    /**
     * @var MeasureUnitConversion
     */
    protected $measureUnitConversion;

    /**
     * @var Dimensions
     */
    protected $dimensionsUnit;

    /**
     * @var Weight
     */
    protected $weightUnit;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager->expects($this->any())->method('get')->willReturn(
            ['inch', 'foot', 'cm', 'm', 'lbs', 'kg']
        );

        $this->measureUnitConversion = new MeasureUnitConversion(
            $this->configManager,
            $this->lengthConfigEntryName,
            $this->weightConfigEntryName
        );

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

    /**
     * @param Dimensions|Weight $unit
     * @param string $shippingUnit
     * @dataProvider convertDataProvider
     */
    public function testConvert($unit, $shippingUnit)
    {
        /** @var Dimensions $convertedUnit */
        $convertedUnit = $this->measureUnitConversion->convert($unit, $shippingUnit);

        $this->assertTrue(in_array(get_class($convertedUnit), [Dimensions::class, Weight::class], null));
        $this->assertEquals($shippingUnit, $convertedUnit->getUnit()->getCode());
    }

    /**
     * @return array
     */
    public function convertDataProvider()
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
        /** @var Dimensions $convertedUnit */
        $convertedUnit = $this->measureUnitConversion->convertDimensions($this->dimensionsUnit, 'inch');

        $this->assertInstanceOf(Dimensions::class, $convertedUnit);
        $this->assertEquals(12.6378021, $convertedUnit->getValue()->getLength());
        $this->assertEquals(25.7480454, $convertedUnit->getValue()->getWidth());
        $this->assertEquals(38.8582887, $convertedUnit->getValue()->getHeight());
        $this->assertEquals('inch', $convertedUnit->getUnit()->getCode());
    }

    public function testConvertWeight()
    {
        /** @var Weight $convertedUnit */
        $convertedUnit = $this->measureUnitConversion->convertWeight($this->weightUnit, 'lbs');

        $this->assertInstanceOf(Weight::class, $convertedUnit);
        $this->assertEquals(114.90493095439999, $convertedUnit->getValue());
        $this->assertEquals('lbs', $convertedUnit->getUnit()->getCode());
    }

    public function testIsDimensionsEnabled()
    {
        $this->assertTrue($this->measureUnitConversion->isDimensionsEnabled($this->dimensionsUnit));
    }

    public function testIsDimensionsDisabled()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())->method('get')->willReturn(
            ['inch', 'foot', 'm', 'lbs', 'kg']
        );
        $this->measureUnitConversion = new MeasureUnitConversion(
            $configManager,
            $this->lengthConfigEntryName,
            $this->weightConfigEntryName
        );
        $this->assertFalse($this->measureUnitConversion->isDimensionsEnabled($this->dimensionsUnit));
    }

    public function testIsWeightEnabled()
    {
        $this->assertTrue($this->measureUnitConversion->isWeightEnabled($this->weightUnit));
    }

    public function testIsWeightDisabled()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $configManager->expects($this->any())->method('get')->willReturn(
            ['inch', 'foot', 'cm', 'm', 'lbs']
        );
        $this->measureUnitConversion = new MeasureUnitConversion(
            $configManager,
            $this->lengthConfigEntryName,
            $this->weightConfigEntryName
        );
        $this->assertFalse($this->measureUnitConversion->isWeightEnabled($this->weightUnit));
    }
}
