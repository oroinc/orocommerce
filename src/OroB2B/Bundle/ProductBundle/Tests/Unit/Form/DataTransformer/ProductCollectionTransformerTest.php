<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductCollectionTransformer;
use Oro\Bundle\ProductBundle\Model\ProductRow;

class ProductCollectionTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductCollectionTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->transformer = new ProductCollectionTransformer();
    }

    public function testTransform()
    {
        $data = ['any' => 'data'];
        $this->assertSame($data, $this->transformer->transform($data));
    }

    /**
     * @param array|null $input
     * @param array|null $expected
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform($input, $expected)
    {
        $this->assertSame($expected, $this->transformer->reverseTransform($input));
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        $productRow1 = $this->createProductRow('sku', 1);
        return [
            'null' => [
                'input' => null,
                'expected' => null,
            ],
            'array' => [
                'input' => [
                    $productRow1,
                    $this->createProductRow('', ''),
                    $this->createProductRow(null, null),
                    null
                ],
                'expected' => [
                    $productRow1,
                ]
            ],
        ];
    }

    /**
     * @param string $sku
     * @param string $qty
     * @return ProductRow
     */
    protected function createProductRow($sku, $qty)
    {
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity= $qty;

        return $productRow;
    }
}
