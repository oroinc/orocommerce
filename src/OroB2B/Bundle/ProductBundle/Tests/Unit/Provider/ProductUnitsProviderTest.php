<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductUnitsProviderTest extends KernelTestCase
{
    /** @var ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function setUp()
    {

        $productUnits[] = new ProductUnit('each');
        $productUnits[] = new ProductUnit('kg');
        $productUnits[] = new ProductUnit('hour');
        $productUnits[] = new ProductUnit('item');
        $productUnits[] = new ProductUnit('set');
        $productUnits[] = new ProductUnit('piece');

        $productUnitRepository = $this
            ->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitRepository->expects($this->once())
            ->method('getAllUnits')
            ->will($this->returnValue($productUnits));

        $entityManager = $this
            ->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($productUnitRepository));

        $this->productUnitsProvider = new ProductUnitsProvider($entityManager);
    }

    public function testGetAvailableProductUnits()
    {
        $expected = [
            'each' => 'product_unit.each.label.full',
            'kg' => 'product_unit.kg.label.full',
            'hour' => 'product_unit.hour.label.full',
            'item' => 'product_unit.item.label.full',
            'set' => 'product_unit.set.label.full',
            'piece' => 'product_unit.piece.label.full',

        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnits());
    }
}
