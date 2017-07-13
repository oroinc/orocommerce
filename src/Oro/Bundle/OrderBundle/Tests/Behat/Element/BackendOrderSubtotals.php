<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\Subtotals;

class BackendOrderSubtotals extends Subtotals
{
    /**
     * {@inheritdoc}
     */
    public function getSubtotal($subtotalName)
    {
        $subtotal = $this->find('xpath', sprintf('//label[text()="%s"]/following-sibling::div/label', $subtotalName));

        return $subtotal->getText();
    }
}
