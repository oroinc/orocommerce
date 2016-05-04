<?php

namespace OroB2B\Bundle\ShippingBundle\Bundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ShippingBundle\Provider\ShippingOptionsProvider;

class ShippingOptionsProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var ShippingOptionsProvider|\PHPUnit_Framework_MockObject_MockObject */
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

        $this->provider = new ShippingOptionsProvider(
            $this->doctrineHelper,
            $this->configManager
        );
    }

    public function tearDown()
    {
        unset($this->provider, $this->configManager, $this->doctrineHelper, $this->em, $this->repo);
    }

    /**
     * @param mixed $method
     * @param mixed $property
     * @param mixed $class
     *
     * @dataProvider classesProvider
     */
    public function testSetClasses($method, $property, $class)
    {
        $this->provider->{$method}($class);
        $this->assertEquals($class, $this->getProperty($this->provider, $property));
    }

    /**
     * @param mixed $method
     * @param mixed $expected
     *
     * @dataProvider unitsProvider
     */
    public function testGetOptions($method, $expected)
    {
        $this->repo->expects($this->atLeastOnce())
            ->method('findAll')
            ->willReturn($expected);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repo);

        $this->assertEquals($this->provider->{$method}(), $expected);
    }

    /**
     * @return array
     */
    public function unitsProvider()
    {
        return [
            ['method' => 'getWeightUnits', 'expected' => []],
            ['method' => 'getLengthUnits', 'expected' => []],
            ['method' => 'getFreightClasses', 'expected' => []],
        ];
    }

    /**
     * @return array
     */
    public function classesProvider()
    {
        return [
            [
                'method' => 'setWeightUnitClass',
                'property' => 'weightUnitClass',
                'class' => 'OroB2B\Bundle\ShippingBundle\Entity\WeightUnit',
            ],
            [
                'method' => 'setLengthUnitClass',
                'property' => 'lengthUnitClass',
                'class' => 'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit',
            ],
            [
                'method' => 'setFreightClassClass',
                'property' => 'freightClassClass',
                'class' => 'OroB2B\Bundle\ShippingBundle\Entity\FreightClass',
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
