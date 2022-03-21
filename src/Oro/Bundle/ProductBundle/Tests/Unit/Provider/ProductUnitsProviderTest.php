<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Contracts\Cache\CacheInterface;

class ProductUnitsProviderTest extends \PHPUnit\Framework\TestCase
{
    private const UNITS = [
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

    use EntityTrait;

    /** @var ProductUnitsProvider */
    private $productUnitsProvider;

    /** @var UnitLabelFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var ProductUnitRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ProductUnitRepository::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $manager = $this->createMock(ObjectManager::class);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(ProductUnit::class)
            ->willReturn($manager);

        $this->formatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->productUnitsProvider = new ProductUnitsProvider(
            $managerRegistry,
            $this->formatter,
            $this->cache
        );
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
        $this->cache->expects(self::once())
            ->method('get')
            ->with('codes')
            ->willReturn($expected);

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
        $this->cache->expects(self::once())
            ->method('get')
            ->with('codes_with_precision')
            ->willReturn($expected);

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnitsWithPrecision());
    }
}
