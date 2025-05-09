<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\ShoppingList;

class PromotionShoppingList extends ShoppingList
{
    #[\Override]
    public function getLineItems()
    {
        return $this->getElements('PromotionShoppingListLineItem');
    }
}
