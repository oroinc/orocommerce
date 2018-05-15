<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductUnitsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var ProductUnitsProvider
     */
    protected $productUnitsProvider;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    public function setUp()
    {
        $units = [
            [
                'code' => 'each',
                'precision' => 1,
            ],
            [
                'code' => 'kg',
                'precision' => 3,
            ],
            [
                'code' => 'hour',
                'precision' => 0,
            ],
            [
                'code' => 'item',
                'precision' => 0,
            ],
            [
                'code' => 'set',
                'precision' => 2,
            ],
            [
                'code' => 'piece',
                'precision' => 1,
            ],
        ];
        $productUnits = [];

        foreach ($units as $unit) {
            $productUnits[] = $this->getEntity(
                ProductUnit::class,
                [
                    'code' => $unit['code'],
                    'defaultPrecision' => $unit['precision']
                ]
            );
        }

        $productUnitRepository = $this
            ->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitRepository->expects($this->once())
            ->method('getAllUnits')
            ->will($this->returnValue($productUnits));

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(ProductUnit::class)
            ->willReturn($productUnitRepository);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ProductUnit::class)
            ->willReturn($manager);

        $this->formatter = $this->getMockBuilder(ProductUnitLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitsProvider = new ProductUnitsProvider($managerRegistry, $this->formatter);
    }

    public function testGetAvailableProductUnits()
    {
        $this->formatter->expects($this->exactly(6))
            ->method('format')
            ->will($this->returnValueMap([
                ['each', false, false, 'oro.product_unit.each.label.full'],
                ['kg', false, false, 'oro.product_unit.kg.label.full'],
                ['hour', false, false, 'oro.product_unit.hour.label.full'],
                ['item', false, false, 'oro.product_unit.item.label.full'],
                ['set', false, false, 'oro.product_unit.set.label.full'],
                ['piece', false, false, 'oro.product_unit.piece.label.full'],
            ]));

        $expected = [
            'oro.product_unit.each.label.full' => 'each',
            'oro.product_unit.kg.label.full' => 'kg',
            'oro.product_unit.hour.label.full' => 'hour',
            'oro.product_unit.item.label.full' => 'item',
            'oro.product_unit.set.label.full' => 'set',
            'oro.product_unit.piece.label.full' => 'piece',
        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnits());
    }

    public function testGetAvailableProductUnitsWithPrecision()
    {
        $expected = [
            'each' => 1,
            'kg' => 3,
            'hour' => 0,
            'item' => 0,
            'set' => 2,
            'piece' => 1,
        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnitsWithPrecision());
    }
}
