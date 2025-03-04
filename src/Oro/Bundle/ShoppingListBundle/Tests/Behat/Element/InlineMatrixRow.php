<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

class InlineMatrixRow extends TableRow
{
    const HEADER_ELEMENT = 'InlineMatrixHeader';

    /**
     * @param int $number Row index number starting from 0
     * @return NodeElement
     */
    #[\Override]
    public function getCellByNumber($number)
    {
        $number = (int) $number;
        $selector = '
            .matrix-order-widget__form__col,
            .matrix-order-widget-table__body-head,
            .matrix-order-widget-table__body-cell,
            .matrix-order-widget-oneflow__cell
        ';
        $columns = $this->findAll('css', $selector);
        self::assertArrayHasKey($number, $columns);

        return $columns[$number];
    }

    /**
     * @param string $header
     * @param string|bool|array $value
     */
    public function fillCellValue($header, $value)
    {
        $cell = $this->getCellByHeader($header);
        $cell->find('css', 'input')->setValue($value);
    }

    /**
     * @param int $columnNumber
     * @return string
     */
    #[\Override]
    protected function getCellElementValue($columnNumber)
    {
        $cellElement = $this->getCellByNumber($columnNumber);
        $input = $cellElement->find('css', 'input');
        $cellElementValue = $cellElement->getText();

        // if it's simple element, just return text
        if (!$input) {
            return $cellElementValue;
        }

        // if it's a checkbox, use 'checked' attribute rather than text value
        if ($input->hasAttribute('type') && 'checkbox' === $input->getAttribute('type')) {
            $cellElementValue = (int) $input->isChecked();
        } else {
            $cellElementValue = $input->getValue();
        }

        return $cellElementValue;
    }
}
