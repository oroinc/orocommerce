<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuickAddRowCollectionTest extends \PHPUnit\Framework\TestCase
{
    private const SKU1 = 'SKU1Абв';
    private const SKU1_UPPER = 'SKU1АБВ';
    private const SKU2 = 'SKU2';
    private const SKU3 = 'SKU3';

    private const QUANTITY1 = 1;
    private const QUANTITY2 = 2.5;

    private const UNIT1 = 'item';

    public function testToString(): void
    {
        $collection = new QuickAddRowCollection();
        self::assertEquals('', (string)$collection);

        $this->addTwoValidRows($collection);
        self::assertEquals(implode(PHP_EOL, ['SKU1Абв, 1', 'SKU2, 2.5']), (string)$collection);

        $this->addInvalidRow($collection);
        self::assertEquals(implode(PHP_EOL, ['SKU1Абв, 1', 'SKU2, 2.5', 'SKU3, 0']), (string)$collection);
    }

    public function testGetValidRows(): void
    {
        $collection = new QuickAddRowCollection();

        $this->addTwoValidRows($collection);
        self::assertCount(2, $collection->getValidRows());

        $this->assertIsSku1Row($collection->getValidRows()->first());
    }

    public function testGetInvalidRows(): void
    {
        $collection = new QuickAddRowCollection();

        $this->addInvalidRow($collection);

        $invalidRows = $collection->getInvalidRows();
        self::assertCount(1, $invalidRows);
        self::assertEquals(self::SKU3, $invalidRows->first()->getSku());
        self::assertEquals(0, $invalidRows->first()->getQuantity());
    }

    public function testMapAndGetProducts(): void
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoValidRows($collection);

        self::assertCount(0, $collection->getProducts());

        $validProduct = [self::SKU1_UPPER => (new Product())->setSku(self::SKU1)];
        $invalidProduct = [self::SKU3 => (new Product())->setSku(self::SKU3)];

        $collection->mapProducts(array_merge($validProduct, $invalidProduct));
        self::assertEquals($validProduct, $collection->getProducts());
    }

    public function testGetSkus(): void
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoValidRows($collection);

        self::assertEquals([self::SKU1, self::SKU2], $collection->getSkus());
    }

    private function addTwoValidRows(QuickAddRowCollection $collection): void
    {
        $collection->add(new QuickAddRow(1, self::SKU1, self::QUANTITY1, self::UNIT1));
        $collection->add(new QuickAddRow(2, self::SKU2, self::QUANTITY2, self::UNIT1));
    }

    private function addInvalidRow(QuickAddRowCollection $collection): void
    {
        $quickAddRow = new QuickAddRow(3, self::SKU3, 0);
        $quickAddRow->addError('Sample error');
        $collection->add($quickAddRow);
    }

    private function assertIsSku1Row(QuickAddRow $row): void
    {
        self::assertEquals(self::SKU1, $row->getSku());
        self::assertEquals(self::QUANTITY1, $row->getQuantity());
    }

    public function testAddError(): void
    {
        $quickAddRowCollection = new QuickAddRowCollection();

        self::assertSame([], $quickAddRowCollection->getErrors());
        self::assertFalse($quickAddRowCollection->hasErrors());

        $quickAddRowCollection->addError('sample message', ['sample_key' => 'sample_value']);

        self::assertSame(
            [['message' => 'sample message', 'parameters' => ['sample_key' => 'sample_value']]],
            $quickAddRowCollection->getErrors()
        );
        self::assertTrue($quickAddRowCollection->hasErrors());
    }

    public function testGetInvalidRowsWhenHasErrors(): void
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowCollection = new QuickAddRowCollection([$quickAddRow]);
        $quickAddRowCollection->addError('sample message', ['sample_key' => 'sample_value']);

        self::assertSame(
            [['message' => 'sample message', 'parameters' => ['sample_key' => 'sample_value']]],
            $quickAddRowCollection->getErrors()
        );
        self::assertTrue($quickAddRowCollection->hasErrors());

        $invalidRows = $quickAddRowCollection->getInvalidRows();
        self::assertSame(
            [['message' => 'sample message', 'parameters' => ['sample_key' => 'sample_value']]],
            $invalidRows->getErrors()
        );
        self::assertTrue($invalidRows->hasErrors());
    }
}
