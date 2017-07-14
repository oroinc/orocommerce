<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingList extends Element implements LineItemsAwareInterface, SubtotalAwareInterface
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
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('ShoppingListTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }

    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('ShoppingListLineItem');
    }
}
