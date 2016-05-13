<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitsProvider $productUnitsProvider
     */
    protected $productUnitsProvider;

    public function setUp()
    {
        $units = ['each', 'kg', 'hour', 'item', 'set', 'piece'];
        $productUnits = [];

        foreach ($units as $v) {
            $productUnits[] = $this->
            getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', $v, 'code');
        }

        $productUnitRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitRepository->expects($this->once())
            ->method('getAllUnits')
            ->will($this->returnValue($productUnits));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($productUnitRepository);

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($manager);

        $this->productUnitsProvider = new ProductUnitsProvider($managerRegistry);
    }

    public function testGetAvailableProductUnits()
    {
        $expected = [
            'each' => 'orob2b.product_unit.each.label.full',
            'kg' => 'orob2b.product_unit.kg.label.full',
            'hour' => 'orob2b.product_unit.hour.label.full',
            'item' => 'orob2b.product_unit.item.label.full',
            'set' => 'orob2b.product_unit.set.label.full',
            'piece' => 'orob2b.product_unit.piece.label.full',

        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnits());
    }

    /**
     * @param string $className
     * @param int|string $idValue
     * @param string $idProperty
     * @return object
     */
    protected function getEntity($className, $idValue, $idProperty = 'id')
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty($idProperty);
        $method->setAccessible(true);
        $method->setValue($entity, $idValue);

        return $entity;
    }
}
