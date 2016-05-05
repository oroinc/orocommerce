<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use OroB2B\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
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
     * @param $configEntryName
     * @param mixed $expected
     *
     * @dataProvider unitsProvider
     */
    public function testGetUnits($entityClass, $configEntryName, $expected)
    {
        $this->repo->expects($this->atLeastOnce())
            ->method('findAll')
            ->willReturn($expected);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with($entityClass)
            ->willReturn($this->repo);

        $this->assertEquals($this->createProvider($entityClass, $configEntryName)->getUnits(), $expected);
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
                'configEntryName' => 'orob2b_shipping.weight_units',
                'expected' => []
            ],
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit',
                'configEntryName' => 'orob2b_shipping.length_units',
                'expected' => []
            ],
            [
                'entityClass' => 'OroB2B\Bundle\ShippingBundle\Entity\FreightClass',
                'configEntryName' => 'orob2b_shipping.freight_classes',
                'expected' => []
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
