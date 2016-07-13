<?php

namespace OroB2B\Bundle\WarehouseBundle\Tests\Model\Data;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use OroB2B\Bundle\WarehouseBundle\Model\Data\ProductUnitTransformer;

class ProductUnitTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider productUnitsProvider
     * @param $unit
     */
    public function testTransformToProductUnit($unit, $expected)
    {
        $productUnitProvider = $this->getMockBuilder(ProductUnitsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitProvider->expects($this->exactly(1))
            ->method('getAvailableProductUnits')
            ->will($this->returnValue([
                'kg' => 'kilogram',
                'item' => 'item',
                'set' => 'set',
                'piece' => 'piece',
                'each' => 'each'
            ]));

        $transformer = new ProductUnitTransformer($productUnitProvider);

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
