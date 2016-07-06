<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider;

class AbstractDefaultProductUnitProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function setUp()
    {
        $configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->defaultProductUnitProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider')
            ->setConstructorArgs([$configManager, $this->managerRegistry])
            ->getMockForAbstractClass();
    }

    /**
     * @dataProvider productUnitPrecisionDataProvider
     * @param array $submittedData
     */
    public function testCreateProductUnitPrecision($submittedData)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($submittedData['unit']);

        $expected = new ProductUnitPrecision();
        $expected->setUnit($productUnit)->setPrecision($submittedData['precision']);

        $class = new \ReflectionClass('OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider');
        $createMethod = $class->getMethod('createProductUnitPrecision');
        $createMethod->setAccessible(true);

        $actual = $createMethod
            ->invokeArgs($this->defaultProductUnitProvider, [$productUnit, $submittedData['precision']]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function productUnitPrecisionDataProvider()
    {
        return [
            'case_with_each' => [
                [
                    'unit' => 'each',
                    'precision' => 0,
                ]
            ],
            'case_with_set' => [
                [
                    'unit' => 'set',
                    'precision' => 3,
                ]
            ],
            'case_with_item' => [
                [
                    'unit' => 'item',
                    'precision' => 5,
                ]
            ],
        ];
    }

    /**
     * @dataProvider repositoryDataProvider
     * @param array $submittedData
     */
    public function testGetRepository($submittedData)
    {
        $class = new \ReflectionClass('OroB2B\Bundle\ProductBundle\Provider\AbstractDefaultProductUnitProvider');
        $getRepositoryMethod = $class->getMethod('getRepository');
        $getRepositoryMethod->setAccessible(true);


        $repository = $this
            ->getMockBuilder($submittedData['class'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager->expects($this->once())
            ->method('getRepository')
            ->with($submittedData['repository'])
            ->willReturn($repository);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $actual = $getRepositoryMethod
            ->invokeArgs($this->defaultProductUnitProvider, [$submittedData['repository']]);

        $this->assertEquals($repository, $actual);
    }

    /**
     * @return array
     */
    public function repositoryDataProvider()
    {
        return [
            'ProductUnitRepository' => [
                [
                    'repository' => 'OroB2BProductBundle:ProductUnit',
                    'class' => 'OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository',
                ]
            ],
            'ProductRepository' => [
                [
                    'repository' => 'OroB2BProductBundle:Product',
                    'class' => 'OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository',
                ]
            ],
        ];
    }
}
