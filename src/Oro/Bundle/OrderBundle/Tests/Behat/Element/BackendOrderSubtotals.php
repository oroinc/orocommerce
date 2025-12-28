<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

use Oro\Bundle\ShoppingListBundle\Tests\Behat\Element\Subtotals;

class BackendOrderSubtotals extends Subtotals
{
    #[\Override]
    public function getSubtotal($subtotalName)
    {
        $subtotal = $this->find('xpath', sprintf('//label[text()="%s"]/following-sibling::div/*', $subtotalName));
        if (null === $subtotal) {
            throw new \LogicException(sprintf('Cannot find "%s" order subtotal', $subtotalName));
        }

        return $subtotal->getText();
    }
}
