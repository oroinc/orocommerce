<?php

namespace Oro\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

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
            getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => $v]);
        }

        $productUnitRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitRepository->expects($this->once())
            ->method('getAllUnits')
            ->will($this->returnValue($productUnits));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($productUnitRepository);

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\ProductBundle\Entity\ProductUnit')
            ->willReturn($manager);
        
        $formatter = $this->getMockBuilder('Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $formatter->expects($this->exactly(6))
            ->method('format')
            ->will($this->returnValueMap([
                ['each', false, false, 'oro.product_unit.each.label.full'],
                ['kg', false, false, 'oro.product_unit.kg.label.full'],
                ['hour', false, false, 'oro.product_unit.hour.label.full'],
                ['item', false, false, 'oro.product_unit.item.label.full'],
                ['set', false, false, 'oro.product_unit.set.label.full'],
                ['piece', false, false, 'oro.product_unit.piece.label.full'],
            ]));

        $this->productUnitsProvider = new ProductUnitsProvider($managerRegistry, $formatter);
    }

    public function testGetAvailableProductUnits()
    {
        $expected = [
            'each' => 'oro.product_unit.each.label.full',
            'kg' => 'oro.product_unit.kg.label.full',
            'hour' => 'oro.product_unit.hour.label.full',
            'item' => 'oro.product_unit.item.label.full',
            'set' => 'oro.product_unit.set.label.full',
            'piece' => 'oro.product_unit.piece.label.full',

        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnits());
    }
}
