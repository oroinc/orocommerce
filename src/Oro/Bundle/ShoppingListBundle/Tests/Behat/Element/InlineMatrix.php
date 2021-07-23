<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

/**
 * It supports two-dimensional matrix forms
 * Usage examples:
 *
 * And I fill "Matrix Grid Form" with:
 * |         | Value X | Value Y | Value Z |
 * | Value A | 2       | -       | -       |
 * | Value B | -       | 3       | -       |
 * | Value C | -       | -       | -       |
 *
 * Then I should see next rows in "Matrix Grid Form" table
 * | Value X | Value Y | Value Z |
 * | 2       |         |         |
 * |         | 3       |         |
 * |         |         |         |
 */
class InlineMatrix extends Table
{
    const TABLE_HEADER_ELEMENT = 'InlineMatrixHeader';
    const TABLE_ROW_HEADER_ELEMENT = 'InlineMatrixRowHeader';
    const TABLE_ROW_ELEMENT = 'InlineMatrixRow';
    const TABLE_ROW_STRICT_ELEMENT = 'InlineMatrixRow';

    /**
     * @param string $elementName
     * @return TableRow[]
     */
    public function getRowElements($elementName)
    {
        return array_map(function (NodeElement $element) use ($elementName) {
            return $this->elementFactory->wrapElement($elementName, $element);
        }, $this->findAll('css', '.matrix-order-widget__grid-body > .matrix-order-widget__form__row'));
    }

    /**
     * @param string $elementName
     * @return InlineMatrixHeader
     */
    public function getHeaderElement($elementName)
    {
        return $this->elementFactory->wrapElement(
            $elementName,
            $this->find('css', '.matrix-order-widget__grid-head-wrapper')
        );
    }

    /**
     * @return bool
     */
    public function isRowHeaderExists()
    {
        try {
            $this->getRowHeader();
        } catch (ElementNotFoundException $exception) {
            return false;
        }

        return true;
    }

    /**
     * @return InlineMatrixRowHeader
     */
    public function getRowHeader()
    {
        return $this->getRowHeaderElement(static::TABLE_ROW_HEADER_ELEMENT);
    }

    /**
     * @param string $elementName
     * @return InlineMatrixRowHeader
     */
    public function getRowHeaderElement($elementName)
    {
        return $this->elementFactory->wrapElement(
            $elementName,
            $this->find('css', '.matrix-order-widget__grid-side-wrapper')
        );
    }

    /**
     * @param string $header
     * @return TableRow
     */
    public function getRowByHeader($header)
    {
        /** @var InlineMatrixRowHeader $tableRowHeader */
        $tableRowHeader = $this->getRowHeader();
        $rowNumber = $tableRowHeader->getRowNumber($header);

        return $this->getRowByNumber($rowNumber + 1);
    }

    public function fill(TableNode $tableNode)
    {
        if ($this->isRowHeaderExists()) {
            $this->fillWithNamedRows($tableNode);
        } else {
            $this->fillWithoutNamedRows($tableNode);
        }
    }

    protected function fillWithNamedRows(TableNode $tableNode)
    {
        $rowsToFill = $tableNode->getRows();
        $headers = array_shift($rowsToFill);

        array_shift($headers);

        foreach ($rowsToFill as $rowKey => $rowToFill) {
            $rowHeader = array_shift($rowToFill);

            foreach ($headers as $headerKey => $header) {
                $value = $rowToFill[$headerKey];
                if ($value === '-') {
                    continue;
                }

                /** @var InlineMatrixRow $row */
                $row = $this->getRowByHeader($rowHeader);
                $row->fillCellValue($header, $value);
            }
        }
    }

    protected function fillWithoutNamedRows(TableNode $tableNode)
    {
        /** @var InlineMatrixRow[] $rows */
        $rows = $this->getRows();
        $rowsToFill = $tableNode->getRows();
        $headers = array_shift($rowsToFill);

        foreach ($rowsToFill as $rowKey => $rowToFill) {
            foreach ($headers as $headerKey => $header) {
                $value = $rowToFill[$headerKey];
                if ($value === '-') {
                    continue;
                }

                $row = $rows[$rowKey];
                $row->fillCellValue($header, $value);
            }
        }
    }
}
