<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Model;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRow;
use OroB2B\Bundle\ProductBundle\Model\QuickAddRowCollection;

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
        $this->assertEquals("SKU1, 1\nSKU2, 2.5", (string) $collection);

        $this->addIncompleteRow($collection);
        $this->assertEquals("SKU1, 1\nSKU2, 2.5\nSKU3, ", (string) $collection);
    }

    public function testGetCompletedRows()
    {
        $collection = new QuickAddRowCollection();
        $this->assertCount(0, $collection->getCompleteRows());

        $this->addIncompleteRow($collection);
        $this->assertCount(0, $collection->getCompleteRows());

        $this->addTwoCompleteRows($collection);
        $this->assertCount(2, $collection->getCompleteRows());
        $this->assertIsSku1Row($collection->getCompleteRows()->first());
    }

    public function testHasCompletedRows()
    {
        $collection = new QuickAddRowCollection();
        $this->assertFalse($collection->hasCompleteRows());

        $this->addIncompleteRow($collection);
        $this->assertFalse($collection->hasCompleteRows());

        $this->addTwoCompleteRows($collection);
        $this->assertTrue($collection->hasCompleteRows());
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

        $collection->setProductsBySku([self::SKU1 => new Product()]);
        $collection->validate();
        $this->assertCount(1, $collection->getValidRows());
        $this->assertEquals(self::SKU1, $collection->getValidRows()->first()->getSku());
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
