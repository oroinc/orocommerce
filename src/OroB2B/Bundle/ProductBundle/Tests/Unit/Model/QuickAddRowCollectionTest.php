<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Form\Type\QuickAddType;
use OroB2B\Bundle\ProductBundle\Model\ProductRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuickAddRowCollectionTest extends \PHPUnit_Framework_TestCase
{
    const SKU1 = 'SKU1';
    const SKU2 = 'SKU2';
    const SKU3 = 'SKU3';

    const QUANTITY1 = 1;
    const QUANTITY2 = 2.5;

    public function testToString()
    {
        $collection = new QuickAddRowCollection();
        $this->assertEquals('', (string) $collection);

        $this->addTwoCompleteRows($collection);
        $this->assertEquals(implode(PHP_EOL, ['SKU1, 1', 'SKU2, 2.5']), (string) $collection);

        $this->addIncompleteRow($collection);
        $this->assertEquals(implode(PHP_EOL, ['SKU1, 1', 'SKU2, 2.5', 'SKU3, ']), (string) $collection);
    }

    public function testGetValidRows()
    {
        $collection = new QuickAddRowCollection();

        $this->addTwoCompleteRows($collection);
        $this->assertCount(0, $collection->getValidRows());

        $this->addTwoValidRows($collection);
        $this->assertCount(2, $collection->getValidRows());
        $this->assertIsSku1Row($collection->getValidRows()->first());
    }

    public function testGetInvalidRows()
    {
        $collection = new QuickAddRowCollection();

        $this->addTwoCompleteRows($collection);
        $this->assertCount(2, $collection->getInvalidRows());
        $this->assertIsSku1Row($collection->getInvalidRows()->first());
    }

    public function testValidate()
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $collection->validate();
        $this->assertCount(0, $collection->getValidRows());

        $products = [self::SKU1 =>  (new Product())->setSku(self::SKU1)];

        $collection->mapProducts($products);
        $collection->validate();

        $this->assertCount(1, $collection->getValidRows());
        $firstRow = $collection->getValidRows()->first();
        $this->assertEquals(self::SKU1, $firstRow->getSku());
        $this->assertEquals(self::SKU1, $firstRow->getProduct()->getSku());
    }

    public function testMapAndGetProducts()
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $this->assertCount(0, $collection->getProducts());

        $validProduct = [self::SKU1 =>  (new Product())->setSku(self::SKU1)];
        $invalidProduct = [self::SKU3 =>  (new Product())->setSku(self::SKU3)];

        $collection->mapProducts(array_merge($validProduct, $invalidProduct));

        $this->assertEquals($validProduct, $collection->getProducts());
    }

    public function testGetSkus()
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $this->assertEquals([self::SKU1, self::SKU2], $collection->getSkus());
    }

    public function testGetFormData()
    {
        $emptyFormData = [QuickAddType::PRODUCTS_FIELD_NAME => []];
        $productRow = new ProductRow();
        $productRow->productSku = self::SKU1;
        $productRow->productQuantity = self::QUANTITY1;
        $expectedFormData = [
            QuickAddType::PRODUCTS_FIELD_NAME => [$productRow]
        ];

        $products = [self::SKU1 =>  (new Product())->setSku(self::SKU1)];

        $collection = new QuickAddRowCollection();
        $this->addTwoCompleteRows($collection);

        $collection->mapProducts([])->validate();
        $this->assertEquals($emptyFormData, $collection->getFormData());

        $collection->mapProducts($products)->validate();
        $this->assertEquals($expectedFormData, $collection->getFormData());
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    private function addTwoCompleteRows(QuickAddRowCollection $collection)
    {
        $collection->add(new QuickAddRow(1, self::SKU1, self::QUANTITY1));
        $collection->add(new QuickAddRow(2, self::SKU2, self::QUANTITY2));
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    private function addTwoValidRows(QuickAddRowCollection $collection)
    {
        $row1 = new QuickAddRow(1, self::SKU1, self::QUANTITY1);
        $row1->setValid(true);

        $row2 = new QuickAddRow(2, self::SKU2, self::QUANTITY2);
        $row2->setValid(true);

        $collection->add($row1);
        $collection->add($row2);
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    private function addIncompleteRow(QuickAddRowCollection $collection)
    {
        $collection->add(new QuickAddRow(3, self::SKU3, null));
    }

    /**
     * @param QuickAddRow $row
     */
    private function assertIsSku1Row(QuickAddRow $row)
    {
        $this->assertEquals(self::SKU1, $row->getSku());
        $this->assertEquals(self::QUANTITY1, $row->getQuantity());
    }
}
