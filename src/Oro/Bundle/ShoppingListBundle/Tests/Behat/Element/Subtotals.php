<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class Subtotals extends Element
{
    /**
     * @param string $subtotalName
     * @return string
     */
    public function getSubtotal($subtotalName)
    {
        /** @var Table $table */
        $table = $this->getElement('Table');
        foreach ($table->getRows() as $row) {
            if (strip_tags($row->getCellByNumber(0)->getHtml()) === $subtotalName) {
                return strip_tags($row->getCellByNumber(1)->getText());
            }
        }

        throw new \LogicException(sprintf('Cannot find "%s" subtotal', $subtotalName));
    }
}
