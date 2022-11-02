<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper\ProductGrouper;

use Oro\Bundle\ProductBundle\Helper\ProductGrouper\ProductRowsGrouper;
use Oro\Bundle\ProductBundle\Model\ProductRow;

class ProductRowsGrouperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductRowsGrouper
     */
    private $grouper;

    protected function setUp(): void
    {
        $this->grouper = new ProductRowsGrouper();
    }

    public function testProcess()
    {
        $products = [
            $this->createProductRowObject('SKU1Абв', 2, 'item'),
            $this->createProductRowObject('SKU2', 3, 'item'),
            $this->createProductRowObject('SKU1Абв', 3, 'item'),
            $this->createProductRowObject('SKU1Абв', 2, 'kg'),
            $this->createProductRowObject('sku1абв', 1, 'item'),
        ];

        $expectedResult = [
            $this->createProductRowObject('SKU1Абв', 6, 'item'),
            $this->createProductRowObject('SKU2', 3, 'item'),
            $this->createProductRowObject('SKU1Абв', 2, 'kg'),
        ];

        $result = $this->grouper->process($products);

        $this->assertCount(3, $result);
        foreach ($result as $i => $productRow) {
            $this->assertEquals($expectedResult[$i], $productRow);
        }
    }

    /**
     * @param string $sku
     * @param float $quantity
     * @param string $unit
     * @return ProductRow
     */
    private function createProductRowObject($sku, $quantity, $unit)
    {
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity = $quantity;
        $productRow->productUnit = $unit;

        return $productRow;
    }
}
