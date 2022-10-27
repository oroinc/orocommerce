<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

/**
 * It supports one-dimensional matrix forms
 * Usage examples:
 *
 * And I fill "Matrix Grid Form" with:
 * | Value A | Value B | Value C |
 * | 2       | 3       | -       |
 *
 * Then I should see next rows in "Matrix Grid Form" table
 * | Value A | Value B | Value C |
 * | 2       | 3       |         |
 */
class InlineMatrixOneDimensional extends Table
{
    const TABLE_HEADER_ELEMENT = 'InlineMatrixHeaderOneDimensional';
    const TABLE_ROW_ELEMENT = 'InlineMatrixRowOneDimensional';
    const TABLE_ROW_STRICT_ELEMENT = 'InlineMatrixRowOneDimensional';

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
     * @return InlineMatrixHeaderOneDimensional
     */
    public function getHeaderElement($elementName)
    {
        return $this->elementFactory->wrapElement(
            $elementName,
            $this->find('css', '.matrix-order-widget__grid-body > .matrix-order-widget__form__row')
        );
    }

    public function fill(TableNode $tableNode)
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
