<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuickAddRowCollectionTest extends \PHPUnit\Framework\TestCase
{
    private const SKU1 = 'SKU1Абв';
    private const SKU2 = 'SKU2';
    private const SKU3 = 'SKU3';

    private const QUANTITY1 = 1.0;
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

        /** @var QuickAddRow $row */
        $row = $collection->getValidRows()->first();
        self::assertEquals(self::SKU1, $row->getSku());
        self::assertEquals(self::QUANTITY1, $row->getQuantity());
    }

    public function testGetInvalidRows(): void
    {
        $collection = new QuickAddRowCollection();

        $this->addInvalidRow($collection);

        $invalidRows = $collection->getInvalidRows();
        self::assertCount(1, $invalidRows);
        /** @var QuickAddRow  $invalidRow */
        $invalidRow = $invalidRows->first();
        self::assertEquals(self::SKU3, $invalidRow->getSku());
        self::assertEquals(0.0, $invalidRow->getQuantity());
    }

    public function testGetProducts(): void
    {
        $collection = new QuickAddRowCollection();
        $this->addTwoValidRows($collection);

        self::assertCount(0, $collection->getProducts());

        $product = new Product();
        $product->setSku(self::SKU1);
        $collection->get(0)->setProduct($product);
        self::assertSame([$product], $collection->getProducts());
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

    /**
     * @dataProvider isValidDataProvider
     */
    public function testIsValid(QuickAddRowCollection $quickAddRowCollection, bool $expected): void
    {
        self::assertSame($expected, $quickAddRowCollection->isValid());
    }

    public function isValidDataProvider(): array
    {
        $quickAddRow = new QuickAddRow(1, 'sku1', 42, 'kg');
        $quickAddRowWithError = new QuickAddRow(2, 'sku2', 242, 'kg');
        $quickAddRowWithError->addError('sample quick add row error');

        $quickAddRowCollectionWithError = new QuickAddRowCollection([$quickAddRowWithError]);
        $quickAddRowCollectionWithError->addError('sample error');

        $emptyQuickAddRowCollectionWithError = new QuickAddRowCollection();
        $emptyQuickAddRowCollectionWithError->addError('sample error');

        return [
            ['quickAddRowCollection' => new QuickAddRowCollection(), 'expected' => true],
            [
                'quickAddRowCollection' => new QuickAddRowCollection([$quickAddRow]),
                'expected' => true,
            ],
            [
                'quickAddRowCollection' => new QuickAddRowCollection([$quickAddRow]),
                'expected' => true,
            ],
            [
                'quickAddRowCollection' => $emptyQuickAddRowCollectionWithError,
                'expected' => false
            ],
            [
                'quickAddRowCollection' => $quickAddRowCollectionWithError,
                'expected' => false,
            ],
            [
                'quickAddRowCollection' => new QuickAddRowCollection([$quickAddRowWithError]),
                'expected' => false
            ],
            [
                'quickAddRowCollection' => new QuickAddRowCollection([$quickAddRowWithError, $quickAddRowWithError]),
                'expected' => false,
            ],
        ];
    }

    public function testAdditionalFieldsCollection(): void
    {
        $collection = new QuickAddRowCollection();
        self::assertSame([], $collection->getAdditionalFields());
        self::assertNull($collection->getAdditionalField('field'));

        $field = new QuickAddField('field', 'value');
        $anotherField = new QuickAddField('anotherField', 'value');
        $collection->addAdditionalField($field);
        $collection->addAdditionalField($anotherField);
        self::assertSame(['field' => $field, 'anotherField' => $anotherField], $collection->getAdditionalFields());
        self::assertSame($field, $collection->getAdditionalField('field'));
        self::assertSame($anotherField, $collection->getAdditionalField('anotherField'));
        self::assertNull($collection->getAdditionalField('unknownField'));
    }
}
