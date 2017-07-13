<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Behat\Element;

interface SubtotalAwareInterface
{
    /**
     * @param string $subtotalName
     * @return string
     */
    public function getSubtotal($subtotalName);
}
