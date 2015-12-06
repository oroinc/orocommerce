<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use OroB2B\Bundle\ProductBundle\Form\DataTransformer\QuickAddRowCollectionToQuickAddOrderTransformer;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddRowCollectionToQuickAddOrderTransformerTest extends \PHPUnit_Framework_TestCase
{
    const SKU_1 = 'sku1';
    const SKU_2 = 'sku2';

    const QUANTITY_1 = 3;
    const QUANTITY_2 = 5;
    /**
     * @var QuickAddRowCollectionToQuickAddOrderTransformer
     */
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->transformer = new QuickAddRowCollectionToQuickAddOrderTransformer();
    }

    /**
     * @dataProvider transformDataProvider
     *
     * @param QuickAddRowCollection|null $collection
     * @param array $expectedArray
     */
    public function testTransformFile($collection, array $expectedArray)
    {
        $this->assertEquals($expectedArray, $this->transformer->transform($collection));
    }

    /**
     * @dataProvider reverseTransformDataProvider
     *
     * @param array|null $value
     */
    public function testReverseTransformFile($value)
    {
        $this->assertEquals($value, $this->transformer->reverseTransform($value));
    }

    /**
     * @return array
     */
    public function transformDataProvider()
    {
        $row1 = new QuickAddRow(1, self::SKU_1, self::QUANTITY_1);
        $row1->setValid(true);
        $row2 = new QuickAddRow(2, self::SKU_2, self::QUANTITY_2);
        $row2->setValid(true);

        $collectionWithValidRows = new QuickAddRowCollection();
        $collectionWithValidRows->add($row1);
        $collectionWithValidRows->add($row2);

        return [
            'null' => [null, []],
            'collection with no valid rows' => [new QuickAddRowCollection(), []],
            'collection with valid rows' => [$collectionWithValidRows, [
                QuickAddType::PRODUCTS_FIELD_NAME => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => self::SKU_1,
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => self::QUANTITY_1
                    ],
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => self::SKU_2,
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => self::QUANTITY_2
                    ]
                ]
            ]]
        ];
    }

    /**
     * @return array
     */
    public function reverseTransformDataProvider()
    {
        return [
            'array' => [[]],
            'null' => [null]
        ];
    }
}
