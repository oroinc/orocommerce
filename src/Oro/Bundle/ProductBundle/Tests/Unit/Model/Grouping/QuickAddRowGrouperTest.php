<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Grouping;

use Oro\Bundle\ProductBundle\Model\Grouping\QuickAddRowGrouper;
use Oro\Bundle\ProductBundle\Model\QuickAddField;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

class QuickAddRowGrouperTest extends \PHPUnit\Framework\TestCase
{
    private QuickAddRowGrouper $grouper;

    protected function setUp(): void
    {
        $this->grouper = new QuickAddRowGrouper();
    }

    private function getRow(
        int $index,
        string $sku,
        float $quantity,
        string $unit,
        ?array $errors = null,
        ?array $additionalFields = null,
        ?string $organization = null
    ): QuickAddRow {
        $quickAddRow = new QuickAddRow($index, $sku, $quantity, $unit, $organization);
        if (null !== $errors) {
            foreach ($errors as $error) {
                $quickAddRow->addError($error);
            }
        }
        if (null !== $additionalFields) {
            foreach ($additionalFields as $additionalField) {
                $quickAddRow->addAdditionalField($additionalField);
            }
        }

        return $quickAddRow;
    }

    private function getField(string $name, mixed $value): QuickAddField
    {
        return new QuickAddField($name, $value);
    }

    public function testGroupProducts(): void
    {
        $collection = new QuickAddRowCollection([
            $this->getRow(1, 'SKU1Абв', 2.0, 'item'),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
            $this->getRow(3, 'SKU1Абв', 3.0, 'item'),
            $this->getRow(4, 'SKU1Абв', 2.0, 'kg'),
            $this->getRow(5, 'sku1абв', 1.0, 'item'),
        ]);

        $expectedCollection = new QuickAddRowCollection([
            $this->getRow(1, 'SKU1Абв', 6.0, 'item'),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
            $this->getRow(4, 'SKU1Абв', 2.0, 'kg'),
        ]);

        $this->grouper->groupProducts($collection);

        self::assertCount(count($expectedCollection), $collection);
        foreach ($collection as $i => $productRow) {
            self::assertEquals($expectedCollection[$i], $productRow, sprintf('Index: %d', $i));
        }
    }

    public function testGroupProductsWithOrganization(): void
    {
        $collection = new QuickAddRowCollection([
            $this->getRow(1, 'sku3', 1.0, 'item'),
            $this->getRow(2, 'sku3', 1.0, 'item', null, null, 'Org 1'),
            $this->getRow(3, 'sku3', 1.0, 'item', null, null, 'Org 2'),
            $this->getRow(4, 'SKU3', 1.0, 'item', null, null, 'ORG 1'),
            $this->getRow(5, 'sku3', 1.0, 'kg', null, null, 'Org 1'),
        ]);

        $expectedCollection = new QuickAddRowCollection([
            $this->getRow(1, 'sku3', 1.0, 'item'),
            $this->getRow(2, 'sku3', 2.0, 'item', null, null, 'Org 1'),
            $this->getRow(3, 'sku3', 1.0, 'item', null, null, 'Org 2'),
            $this->getRow(5, 'sku3', 1.0, 'kg', null, null, 'Org 1'),
        ]);

        $this->grouper->groupProducts($collection);

        self::assertCount(count($expectedCollection), $collection);
        foreach ($collection as $i => $productRow) {
            self::assertEquals($expectedCollection[$i], $productRow, sprintf('Index: %d', $i));
        }
    }

    public function testGroupProductsWithErrors(): void
    {
        $collection = new QuickAddRowCollection([
            $this->getRow(1, 'SKU1Абв', 2.0, 'item', ['error 1']),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
            $this->getRow(3, 'SKU1Абв', 3.0, 'item', ['error 2']),
            $this->getRow(4, 'sku1абв', 1.0, 'item'),
        ]);
        $collection->addError('some error');

        $expectedCollection = new QuickAddRowCollection([
            $this->getRow(1, 'SKU1Абв', 6.0, 'item', ['error 1', 'error 2']),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
        ]);

        $this->grouper->groupProducts($collection);

        self::assertEquals([['message' => 'some error', 'parameters' => []]], $collection->getErrors());
        self::assertCount(count($expectedCollection), $collection);
        foreach ($collection as $i => $productRow) {
            self::assertEquals($expectedCollection[$i], $productRow, sprintf('Index: %d', $i));
        }
    }

    public function testGroupProductsWithAdditionalFields(): void
    {
        $collection = new QuickAddRowCollection([
            $this->getRow(1, 'SKU1Абв', 2.0, 'item', null, [$this->getField('f1', 'v1'), $this->getField('f2', 'v2')]),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
            $this->getRow(3, 'SKU1Абв', 3.0, 'item', null, [$this->getField('f1', 'v1'), $this->getField('f3', 'v3')]),
            $this->getRow(4, 'sku1абв', 1.0, 'item'),
        ]);
        $someField = $this->getField('someField', 10);
        $collection->addAdditionalField($someField);

        $expectedCollection = new QuickAddRowCollection([
            $this->getRow(
                1,
                'SKU1Абв',
                6.0,
                'item',
                null,
                [$this->getField('f1', 'v1'), $this->getField('f2', 'v2'), $this->getField('f3', 'v3')]
            ),
            $this->getRow(2, 'SKU2', 3.0, 'item'),
        ]);

        $this->grouper->groupProducts($collection);

        self::assertSame($someField, $collection->getAdditionalField('someField'));
        self::assertCount(count($expectedCollection), $collection);
        foreach ($collection as $i => $productRow) {
            self::assertEquals($expectedCollection[$i], $productRow, sprintf('Index: %d', $i));
        }
    }
}
