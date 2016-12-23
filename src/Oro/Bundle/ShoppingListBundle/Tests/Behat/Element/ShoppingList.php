<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class ShoppingList extends Element
{
    /**
     * @param string $title
     */
    public function assertTitle($title)
    {
        $titleElement = $this->findElementContains('ShoppingListTitle', $title);
        self::assertTrue($titleElement->isValid(), sprintf('Title "%s", was not match to current title', $title));
    }
}
