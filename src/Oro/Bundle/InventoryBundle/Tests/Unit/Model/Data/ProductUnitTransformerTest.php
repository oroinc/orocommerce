<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Model\Data;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\InventoryBundle\Model\Data\ProductUnitTransformer;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider productUnitsProvider
     */
    public function testTransformToProductUnit($unit, $expected)
    {
        $productUnitProvider = $this->getMockBuilder(ProductUnitsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitProvider->expects($this->exactly(1))
            ->method('getAvailableProductUnits')
            ->will($this->returnValue([
                'kilogram' => 'kg',
                'item' => 'item',
                'set' => 'set',
                'piece' => 'piece',
                'each' => 'each'
            ]));

        $transformer = new ProductUnitTransformer($productUnitProvider, (new InflectorFactory())->build());

        $code = $transformer->transformToProductUnit($unit);

        $this->assertEquals($code, $expected);
    }

    public function productUnitsProvider()
    {
        return [
            [
                'unit' => 'kilogram',
                'expected' => 'kg'
            ],
            [
                'unit' => 'kilograms',
                'expected' => 'kg'
            ],
            [
                'unit' => 'item',
                'expected' => 'item'
            ],
            [
                'unit' => 'items',
                'expected' => 'item'
            ],
            [
                'unit' => 'piece',
                'expected' => 'piece'
            ],
            [
                'unit' => 'pieces',
                'expected' => 'piece'
            ],
            [
                'unit' => 'set',
                'expected' => 'set'
            ],
            [
                'unit' => 'sets',
                'expected' => 'set'
            ]
        ];
    }
}
