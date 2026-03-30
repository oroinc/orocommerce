<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

/**
 * Represents a single order line item in the inline draft edit form.
 *
 * The edit form is rendered by orderLineItemDraftUpdate.html.twig:
 * - SKU is inside td.grid-body-cell-productSku
 * - Taxes are inside .order-line-item-draft-update-calculations__taxes > table (2 tables via renderTaxes macro)
 * - Discounts are inside .order-line-item-draft-update-calculations__discounts > table (via renderDiscounts macro)
 */
class TaxBackendOrderDraftEditLineItem extends Element
{
    // First taxes table headers: Unit Price / Row Total breakdown (renderTaxes macro, table 1)
    private const string INCL_TAX_CELL_HEADER = 'incl. tax';
    private const string EXCL_TAX_CELL_HEADER = 'excl. tax';
    private const string TAX_AMOUNT_CELL_HEADER = 'tax amount';

    // Second taxes table headers: Tax/Rate/Taxable Amount breakdown (renderTaxes macro, table 2)
    private const string TAX_CELL_HEADER = 'tax';
    private const string RATE_CELL_HEADER = 'rate';
    private const string TAXABLE_AMOUNT_CELL_HEADER = 'taxable amount';

    // Discounts table headers (renderDiscounts macro, variant with incl/excl tax)
    private const string AFTER_DISC_INCL_TAX_CELL_HEADER = 'after disc. incl. tax';
    private const string AFTER_DISC_EXCL_TAX_CELL_HEADER = 'after disc. excl. tax';
    private const string DISC_AMOUNT_CELL_HEADER = 'disc. amount';

    public function getProductSKU(): string
    {
        $cell = $this->find('css', 'td.grid-body-cell-productSku');
        self::assertNotNull($cell, 'Cannot find product SKU cell in draft edit line item');

        return trim($cell->getText());
    }

    /**
     * Returns tax values from the Unit Price / Row Total breakdown table (renderTaxes macro, first table).
     *
     * Returns array matching the table structure (including the row-label column):
     * [
     *   ['Unit Price', $inclTax, $exclTax, $taxAmount],
     *   ['Row Total',  $inclTax, $exclTax, $taxAmount],
     * ]
     */
    public function getTaxes(): array
    {
        $taxesContainer = $this->find('css', '.order-line-item-draft-update-calculations__taxes');
        self::assertNotNull($taxesContainer, 'Cannot find taxes container in draft edit line item');

        // First table – Unit Price / Row Total breakdown
        $table = $taxesContainer->find('css', 'table');
        self::assertNotNull($table, 'Cannot find taxes table in draft edit line item');

        $rows = $table->findAll('xpath', 'tbody/tr');
        self::assertCount(2, $rows, 'Expected 2 tax rows (Unit Price and Row Total)');

        // Build a header-name → column-index map (case-insensitive).
        // The first <th> is empty (label column at index 0).
        $headerIndex = [];
        foreach ($table->findAll('xpath', 'thead/tr/th') as $i => $th) {
            $headerIndex[strtolower(trim($th->getText()))] = $i;
        }

        $getCellValue = static function (NodeElement $row, int $index): string {
            $cells = $row->findAll('xpath', 'td');

            return trim($cells[$index]->getText());
        };

        return [
            [
                $getCellValue($rows[0], 0), // "Unit Price" label
                $getCellValue($rows[0], $headerIndex[self::INCL_TAX_CELL_HEADER]),
                $getCellValue($rows[0], $headerIndex[self::EXCL_TAX_CELL_HEADER]),
                $getCellValue($rows[0], $headerIndex[self::TAX_AMOUNT_CELL_HEADER]),
            ],
            [
                $getCellValue($rows[1], 0), // "Row Total" label
                $getCellValue($rows[1], $headerIndex[self::INCL_TAX_CELL_HEADER]),
                $getCellValue($rows[1], $headerIndex[self::EXCL_TAX_CELL_HEADER]),
                $getCellValue($rows[1], $headerIndex[self::TAX_AMOUNT_CELL_HEADER]),
            ],
        ];
    }

    /**
     * Returns rows from the Tax/Rate/Taxable Amount breakdown table (renderTaxes macro, second table).
     *
     * Returns an empty array when no tax breakdown rows exist.
     */
    public function getTaxResults(): array
    {
        $taxesContainer = $this->find('css', '.order-line-item-draft-update-calculations__taxes');
        self::assertNotNull($taxesContainer, 'Cannot find taxes container in draft edit line item');

        $tables = $taxesContainer->findAll('css', 'table');
        if (count($tables) < 2) {
            return [];
        }

        $table = $tables[1]; // second table: Tax/Rate/Taxable Amount/Tax Amount

        // Build a header-name → column-index map (case-insensitive)
        $headerIndex = [];
        foreach ($table->findAll('xpath', 'thead/tr/th') as $i => $th) {
            $headerIndex[strtolower(trim($th->getText()))] = $i;
        }

        $getCellValue = static function (NodeElement $row, int $index): string {
            $cells = $row->findAll('xpath', 'td');

            return trim($cells[$index]->getText());
        };

        $result = [];
        foreach ($table->findAll('xpath', 'tbody/tr') as $row) {
            $result[] = [
                $getCellValue($row, $headerIndex[self::TAX_CELL_HEADER]),
                $getCellValue($row, $headerIndex[self::RATE_CELL_HEADER]),
                $getCellValue($row, $headerIndex[self::TAXABLE_AMOUNT_CELL_HEADER]),
                $getCellValue($row, $headerIndex[self::TAX_AMOUNT_CELL_HEADER]),
            ];
        }

        return $result;
    }

    /**
     * Returns discount values from the discounts table (renderDiscounts macro output).
     *
     * Returns array matching the table structure (including the row-label column):
     * [
     *   ['Row Total', $afterDiscInclTax, $afterDiscExclTax, $discAmount],
     * ]
     */
    public function getDiscounts(): array
    {
        $discountsContainer = $this->find('css', '.order-line-item-draft-update-calculations__discounts');
        self::assertNotNull($discountsContainer, 'Cannot find discounts container in draft edit line item');

        $table = $discountsContainer->find('css', 'table');
        self::assertNotNull($table, 'Cannot find discounts table in draft edit line item');

        $rows = $table->findAll('xpath', 'tbody/tr');
        self::assertCount(1, $rows, 'Expected 1 discount row (Row Total)');

        // Build a header-name → column-index map (case-insensitive).
        // The first <th> is empty (label column at index 0).
        $headerIndex = [];
        foreach ($table->findAll('xpath', 'thead/tr/th') as $i => $th) {
            $headerIndex[strtolower(trim($th->getText()))] = $i;
        }

        $getCellValue = static function (NodeElement $row, int $index): string {
            $cells = $row->findAll('xpath', 'td');

            return trim($cells[$index]->getText());
        };

        return [
            [
                $getCellValue($rows[0], 0), // "Row Total" label
                $getCellValue($rows[0], $headerIndex[self::AFTER_DISC_INCL_TAX_CELL_HEADER]),
                $getCellValue($rows[0], $headerIndex[self::AFTER_DISC_EXCL_TAX_CELL_HEADER]),
                $getCellValue($rows[0], $headerIndex[self::DISC_AMOUNT_CELL_HEADER]),
            ],
        ];
    }
}
