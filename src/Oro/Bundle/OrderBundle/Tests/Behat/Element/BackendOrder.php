<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Table;

class BackendOrder extends Order
{
    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getLineItemsFromTable('BackendOrderLineItem');
    }

    /**
     * {@inheritdoc}
     */
    public function getSubtotal($subtotalName)
    {
        /** @var BackendOrderSubtotals $subtotals */
        $subtotals = $this->getElement('BackendOrderSubtotals');

        return $subtotals->getSubtotal($subtotalName);
    }

    /**
     * @param string $lineItemElement
     * @return array|LineItemInterface[]
     */
    protected function getLineItemsFromTable($lineItemElement)
    {
        /** @var Table $lineItemsTable */
        $lineItemsTable = $this->getElement('BackendOrderLineItemsTable');

        return $lineItemsTable->getRowElements($lineItemElement);
    }
}
