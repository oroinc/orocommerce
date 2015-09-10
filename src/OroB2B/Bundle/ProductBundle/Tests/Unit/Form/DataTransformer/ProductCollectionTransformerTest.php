<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\ProductCollectionTransformer;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductRowType;

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
                        ProductRowType::PRODUCT_SKU_FIELD_NAME => 'sku',
                        ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => 1,
                    ],
                    [
                        ProductRowType::PRODUCT_SKU_FIELD_NAME => '',
                        ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => '',
                    ],
                    [
                        ProductRowType::PRODUCT_SKU_FIELD_NAME => null,
                        ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => null,
                    ],
                    []
                ],
                'expected' => [
                    [
                        ProductRowType::PRODUCT_SKU_FIELD_NAME => 'sku',
                        ProductRowType::PRODUCT_QUANTITY_FIELD_NAME => 1,
                    ]
                ]
            ],
        ];
    }
}
