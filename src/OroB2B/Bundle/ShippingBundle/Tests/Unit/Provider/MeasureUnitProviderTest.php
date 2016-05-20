<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

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

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MeasureUnitProvider(
            $this->repository,
            $this->configManager,
            self::CONFIG_ENTRY_NAME
        );
    }

    public function tearDown()
    {
        unset($this->repository, $this->doctrineHelper, $this->provider);
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
}
