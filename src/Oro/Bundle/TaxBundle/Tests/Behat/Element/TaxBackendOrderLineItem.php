<?php

namespace Oro\Bundle\TaxBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class TaxBackendOrderLineItem extends TableRow
{
    private const TAXES_CELL_HEADER = 'TAXES';
    private const TAX_AMOUNT_CELL_HEADER = 'Tax Amount';
    private const INCL_TAX_CELL_HEADER = 'Incl. Tax';
    private const EXCL_TAX_CELL_HEADER = 'Excl. Tax';

    /**
     * {@inheritdoc}
     */
    public function getProductSKU()
    {
        return $this->getCellValue('SKU');
    }

    /**
     * @return array [$rowTotalInclTax, $rowTotalExclTax, $taxAmount]
     */
    public function getTaxesWithUnitPrice()
    {
        $cellElement = $this->getCellByHeader(self::TAXES_CELL_HEADER);

        /** @var Table $taxesTable */
        $taxesTable = $this->elementFactory->createElement('BackendLineItemTaxTable', $cellElement);

        return [
            $taxesTable->getRowByNumber(1)->getCellValue(self::INCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(1)->getCellValue(self::EXCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(1)->getCellValue(self::TAX_AMOUNT_CELL_HEADER)
        ];
    }

    /**
     * @return array [
     *      $unitPriceInclTax,
     *      $unitPriceExclTax,
     *      $unitPriceTaxAmount,
     *      $rowTotalInclTax,
     *      $rowTotalExclTax,
     *      $rowTotalTaxAmount
     * ]
     */
    public function getTaxes()
    {
        $cellElement = $this->getCellByHeader(self::TAXES_CELL_HEADER);

        /** @var Table $taxesTable */
        $taxesTable = $this->elementFactory->createElement('BackendLineItemTaxTable', $cellElement);

        return [
            $taxesTable->getRowByNumber(1)->getCellValue(self::INCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(1)->getCellValue(self::EXCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(1)->getCellValue(self::TAX_AMOUNT_CELL_HEADER),

            $taxesTable->getRowByNumber(2)->getCellValue(self::INCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(2)->getCellValue(self::EXCL_TAX_CELL_HEADER),
            $taxesTable->getRowByNumber(2)->getCellValue(self::TAX_AMOUNT_CELL_HEADER)
        ];
    }
}
