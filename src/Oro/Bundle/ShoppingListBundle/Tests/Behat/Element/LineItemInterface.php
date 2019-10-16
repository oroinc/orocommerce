<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface LineItemInterface
{
    /**
     * @return string
     */
    public function getProductSKU(): string;
}
