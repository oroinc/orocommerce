<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

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
            getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $v]);
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
        
        $formatter = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $formatter->expects($this->exactly(6))
            ->method('format')
            ->will($this->returnValueMap([
                ['each', false, false, 'orob2b.product_unit.each.label.full'],
                ['kg', false, false, 'orob2b.product_unit.kg.label.full'],
                ['hour', false, false, 'orob2b.product_unit.hour.label.full'],
                ['item', false, false, 'orob2b.product_unit.item.label.full'],
                ['set', false, false, 'orob2b.product_unit.set.label.full'],
                ['piece', false, false, 'orob2b.product_unit.piece.label.full'],
            ]));

        $this->productUnitsProvider = new ProductUnitsProvider($managerRegistry, $formatter);
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
}
