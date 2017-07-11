<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface LineItemsAwareInterface
{
    /**
     * @return array|LineItemInterface[]
     */
    public function getLineItems();
}
