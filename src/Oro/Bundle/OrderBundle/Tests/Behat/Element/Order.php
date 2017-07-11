<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemsAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class Order extends Element implements LineItemsAwareInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getElements('OrderLineItem');
    }
}
