<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\WeightUnit;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class AbstractMeasureUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var AbstractMeasureUnitProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repo;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var UnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $labelFormatter;

    protected function setUp()
    {
        $this->repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->labelFormatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function tearDown()
    {
        unset(
            $this->provider,
            $this->configManager,
            $this->doctrineHelper,
            $this->em,
            $this->repo,
            $this->labelFormatter
        );
    }

    /**
     * @param mixed $entityClass
     * @param mixed $ormData
     * @param mixed $configEntryName
     * @param mixed $configData
     * @param mixed $expected
     * @param mixed $onlyEnabled
     *
     * @dataProvider unitsProvider
     */
    public function testGetUnits(
        $entityClass,
        $ormData,
        $configEntryName,
        $configData,
        $expected,
        $onlyEnabled = true
    ) {
        $this->repo->expects($this->atLeastOnce())
            ->method('findAll')
            ->willReturn($ormData);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with($entityClass)
            ->willReturn($this->repo);

        if ($onlyEnabled) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with($configEntryName, false)
                ->willReturn($configData);
        } else {
            $this->configManager->expects($this->never())
                ->method('get');
        }

        $units = $this->createProvider($entityClass, $configEntryName)->getUnits($onlyEnabled);

        $this->assertEquals($units, $expected);

        if (count($units)) {
            foreach ($units as $unit) {
                $this->assertInstanceOf($entityClass, $unit);
            }
        }
    }

    /**
     * @param mixed $entityClass
     * @param mixed $configEntryName
     *
     * @return AbstractMeasureUnitProvider
     */
    private function createProvider($entityClass, $configEntryName)
    {
        $provider = new AbstractMeasureUnitProvider($this->doctrineHelper, $this->configManager);
        $provider->setEntityClass($entityClass);
        $provider->setConfigEntryName($configEntryName);
        $provider->setLabelFormatter($this->labelFormatter);
        $this->assertEquals($configEntryName, $this->getProperty($provider, 'configEntryName'));
        $this->assertEquals($entityClass, $this->getProperty($provider, 'entityClass'));

        return $provider;
    }

    /**
     * @return array
     */
    public function unitsProvider()
    {
        return [
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                'ormData' => [
                    (new WeightUnit())->setCode('test 1'),
                    (new WeightUnit())->setCode('test 2'),
                    (new WeightUnit())->setCode('test 3')
                ],
                'configEntryName' => 'orob2b_shipping.weight_units',
                'configData' => ['test 1' => 'test 1'],
                'expected' => [(new WeightUnit())->setCode('test 1')]
            ],
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                'ormData' => [
                    (new WeightUnit())->setCode('test 1'),
                    (new WeightUnit())->setCode('test 2'),
                    (new WeightUnit())->setCode('test 3')
                ],
                'configEntryName' => 'orob2b_shipping.weight_units',
                'configData' => ['test 1' => 'test 1'],
                'expected' => [
                    (new WeightUnit())->setCode('test 1'),
                    (new WeightUnit())->setCode('test 2'),
                    (new WeightUnit())->setCode('test 3')
                ],
                'onlyEnabled' => false,
            ],
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                'ormData' => [
                ],
                'configEntryName' => 'orob2b_shipping.weight_units',
                'configData' => ['test 1' => 'test 1'],
                'expected' => [
                ],
            ],
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
                'ormData' => [
                ],
                'configEntryName' => 'orob2b_shipping.weight_units',
                'configData' => ['test 1' => 'test 1'],
                'expected' => [
                ],
                'onlyEnabled' => false,
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getUnitsCodesProvider
     */
    public function testGetUnitsCodes(array $inputData, array $expectedData)
    {
        $this->repo->expects($this->once())
            ->method('findAll')
            ->willReturn($inputData['units']);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with('entityClass')
            ->willReturn($this->repo);

        $provider = $this->createProvider('entityClass', 'configKey');
        $this->assertEquals($expectedData, $provider->getUnitsCodes(false));
    }

    /**
     * @return array
     */
    public function getUnitsCodesProvider()
    {
        return [
            [
                'input' => [
                    'units' => [
                        $this->getMeasureUnit('kg'),
                        $this->getMeasureUnit('mg'),
                    ],
                ],
                'expected' => ['kg', 'mg'],
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider formatUnitsCodesProvider
     */
    public function testFormatUnitsCodes(array $inputData, array $expectedData)
    {
        $this->labelFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(function ($item, $isShort) {
                return $item . '.formatted' . ($isShort ? '.short' : '');
            });

        $provider = $this->createProvider('entityClass', 'configKey');
        $this->assertEquals($expectedData, $provider->formatUnitsCodes($inputData['codes'], $inputData['isShort']));
    }

    /**
     * @return array
     */
    public function formatUnitsCodesProvider()
    {
        return [
            'normal' => [
                'input' => [
                    'codes' => ['kg', 'mg'],
                    'isShort' => false,
                ],
                'expected' => ['kg.formatted', 'mg.formatted'],
            ],
            'short' => [
                'input' => [
                    'codes' => ['kg', 'mg'],
                    'isShort' => true,
                ],
                'expected' => ['kg.formatted.short', 'mg.formatted.short'],
            ],
        ];
    }

    /**
     * @return MeasureUnitInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMeasureUnit($code)
    {
        $unit = $this->getMock('OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface');
        $unit->expects($this->any())
            ->method('getCode')
            ->willReturn($code);

        return $unit;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }
}
