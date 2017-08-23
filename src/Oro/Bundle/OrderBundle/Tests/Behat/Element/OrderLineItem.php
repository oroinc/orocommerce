<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class OrderLineItem extends Element implements LineItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProductSKU()
    {
        return $this->getElement('OrderLineItemProductSku')->getText();
    }
}
