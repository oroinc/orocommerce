<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\Order;

class PromotionOrder extends Order
{
    #[\Override]
    public function getLineItems()
    {
        return $this->getElements('PromotionOrderLineItem');
    }
}
