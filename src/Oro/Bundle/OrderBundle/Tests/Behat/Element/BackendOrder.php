<?php

namespace Oro\Bundle\OrderBundle\Tests\Behat\Element;

class BackendOrder extends Order
{
    #[\Override]
    public function getSubtotal($subtotalName)
    {
        /** @var BackendOrderSubtotals $subtotals */
        $subtotals = $this->getElement('BackendOrderSubtotals');

        return $subtotals->getSubtotal($subtotalName);
    }
}
