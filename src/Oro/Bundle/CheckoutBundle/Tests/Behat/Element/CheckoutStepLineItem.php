<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\LineItemInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Element;

class CheckoutStepLineItem extends Element implements LineItemInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProductSKU(): string
    {
        return $this->getElement('CheckoutStepLineItemProductSku')->getText();
    }
}
