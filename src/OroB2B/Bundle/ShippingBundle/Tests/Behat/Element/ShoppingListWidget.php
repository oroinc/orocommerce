<?php

namespace Oro\Bundle\ShippingBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingListWidget extends Element
{
    /**
     * @param $name
     * @return ShoppingListItem
     * @throws ExpectationException
     */
    public function getShoppingList($name)
    {
        $items = $this->getPage()->findAll('css', '.order-widget__item');
        self::assertNotCount(0, $items, 'There are no shopping lists in user basket');

        /** @var NodeElement $item */
        foreach ($items as $item) {
            $currentItemName = $item->find('css', '.shopping-list-label')->getText();
            if (preg_match(sprintf('/%s/i', $name), $currentItemName)) {
                return $this->elementFactory->wrapElement('ShoppingListItem', $item);
            }
        }

        self::fail(sprintf('Threre are no shopping list with "%s" name', $name));
    }
}
