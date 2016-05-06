<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ShippingBundle\Entity\FreightClass;
use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
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
    }

    public function tearDown()
    {
        unset($this->provider, $this->configManager, $this->doctrineHelper, $this->em, $this->repo);
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
        ];
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
