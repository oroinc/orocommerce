<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Provider\MeasureUnitProvider;

class MeasureUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    const CONFIG_ENTRY_NAME = 'orob2b_shipping.weight_units';

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var MeasureUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $labelFormatter;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->labelFormatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MeasureUnitProvider(
            $this->repository,
            $this->configManager,
            $this->labelFormatter,
            self::CONFIG_ENTRY_NAME
        );
    }

    public function tearDown()
    {
        unset($this->repository, $this->doctrineHelper, $this->provider, $this->labelFormatter);
    }

    /**
     * @param mixed $ormData
     * @param mixed $configData
     * @param mixed $expected
     * @param bool $onlyEnabled
     *
     * @dataProvider unitsProvider
     */
    public function testGetUnits(
        $ormData,
        $configData,
        $expected,
        $onlyEnabled = true
    ) {
        $this->repository->expects($this->atLeastOnce())->method('findAll')->willReturn($ormData);

        if ($onlyEnabled) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with(self::CONFIG_ENTRY_NAME, false)
                ->willReturn($configData);
        } else {
            $this->configManager->expects($this->never())->method('get');
        }

        $units = $this->provider->getUnits($onlyEnabled);

        $this->assertEquals($units, $expected);

        if (count($units)) {
            foreach ($units as $unit) {
                $this->assertInstanceOf('OroB2B\Bundle\ShippingBundle\Entity\WeightUnit', $unit);
            }
        }
    }

    /**
     * @return array
     */
    public function unitsProvider()
    {
        $weightUnit1 = (new WeightUnit())->setCode('test 1');
        $weightUnit2 = (new WeightUnit())->setCode('test 2');
        $weightUnit3 = (new WeightUnit())->setCode('test 3');

        return [
            [
                'ormData' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [$weightUnit1]
            ],
            [
                'ormData' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [$weightUnit1, $weightUnit2, $weightUnit3],
                'onlyEnabled' => false
            ],
            [
                'ormData' => [],
                'configData' => ['test 1' => 'test 1'],
                'expected' => []
            ],
            [
                'ormData' => [],
                'configData' => ['test 1' => 'test 1'],
                'expected' => [],
                'onlyEnabled' => false
            ]
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getFormattedUnitsProvider
     */
    public function testGetFormattedUnits(array $inputData, array $expectedData)
    {
        $this->repository->expects($this->atLeastOnce())->method('findAll')->willReturn($inputData['orm']);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(self::CONFIG_ENTRY_NAME, false)
            ->willReturn($inputData['config']);

        $this->labelFormatter->expects($this->any())
            ->method('formatChoices')
            ->with($inputData['orm'])
            ->willReturn($expectedData);

        $this->assertEquals(
            $expectedData,
            $this->provider->getFormattedUnits($inputData['isShort'])
        );
    }

    /**
     * @return array
     */
    public function getFormattedUnitsProvider()
    {
        return [
            'normal' => [
                'input' => [
                    'orm' => [
                        (new WeightUnit())->setCode('kg'),
                        (new WeightUnit())->setCode('mg'),
                    ],
                    'config' => ['kg', 'mg'],
                    'isShort' => false,
                ],
                'expected' => ['kg.formatted', 'mg.formatted'],
            ],
            'short' => [
                'input' => [
                    'orm' => [
                        (new WeightUnit())->setCode('kg'),
                        (new WeightUnit())->setCode('mg'),
                    ],
                    'config' => ['kg', 'mg'],
                    'isShort' => true,
                ],
                'expected' => ['kg.formatted.short', 'mg.formatted.short'],
            ],
        ];
    }
}
