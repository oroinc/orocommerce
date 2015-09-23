<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductCollectionTransformer;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

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
        return [
            'null' => [
                'input' => null,
                'expected' => null,
            ],
            'array' => [
                'input' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'sku',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 1,
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => '',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => '',
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => null,
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                    ],
                    []
                ],
                'expected' => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => 'sku',
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => 1,
                    ]
                ]
            ],
        ];
    }
}
