<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\SubtotalAwareInterface;
use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\Subtotals;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\EntityPage;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\TableRow;

/**
 * Base Order element for the frontend usage.
 */
class Order extends EntityPage implements LineItemsAwareInterface, SubtotalAwareInterface
{
    /**
     * @param string $subtotalName
     * @return string
     */
    public function getSubtotal($subtotalName)
    {
        /** @var Subtotals $subtotals */
        $subtotals = $this->getElement('Subtotals');

        return $subtotals->getSubtotal($subtotalName);
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('OrderLineItem');
    }

    /**
     * {@inheritdoc}
     */
    public function assertPageContainsValue($label, $value)
    {
        $rowColumn = $this->findElementContains('FirstTableRowColumn', $label);
        if (!$rowColumn->isIsset()) {
            self::fail(sprintf('Can\'t find "%s" label', $label));
        }

        /** @var TableRow $rowElement */
        $rowElement = $this->elementFactory->wrapElement('TableRow', $rowColumn->getParent());

        if ($rowElement->getCellByNumber(1)->getText() === Form::normalizeValue($value)) {
            return;
        }

        self::fail(sprintf('Found "%s" label, but it doesn\'t have "%s" value', $label, $value));
    }
}
