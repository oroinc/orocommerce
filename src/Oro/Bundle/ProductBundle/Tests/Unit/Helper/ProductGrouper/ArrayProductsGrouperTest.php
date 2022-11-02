<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ArrayProductsGrouper;

class ArrayProductsGrouperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ArrayProductsGrouper
     */
    private $grouper;

    protected function setUp(): void
    {
        $this->grouper = new ArrayProductsGrouper();
    }

    public function testProcess()
    {
        $products = [
            $this->createProductRowArray('SKU1Абв', 2, 'item'),
            $this->createProductRowArray('SKU2', 3, 'item'),
            $this->createProductRowArray('SKU1Абв', 3, 'item'),
            $this->createProductRowArray('SKU1Абв', 2, 'kg'),
            $this->createProductRowArray('sku1абв', 1, 'item'),
            ['productQuantity' => 10],
        ];

        $expectedResult = [
            $this->createProductRowArray('SKU1Абв', 6, 'item'),
            $this->createProductRowArray('SKU2', 3, 'item'),
            $this->createProductRowArray('SKU1Абв', 2, 'kg'),
            ['productQuantity' => 10],
        ];

        $result = $this->grouper->process($products);

        $this->assertCount(4, $result);
        foreach ($result as $i => $productRow) {
            $this->assertEquals($expectedResult[$i], $productRow);
        }
    }

    /**
     * @param string $sku
     * @param float $quantity
     * @param string $unit
     * @return array
     */
    private function createProductRowArray($sku, $quantity, $unit)
    {
        return [
            'productSku' => $sku,
            'productQuantity' => $quantity,
            'productUnit' => $unit,
        ];
    }
}
