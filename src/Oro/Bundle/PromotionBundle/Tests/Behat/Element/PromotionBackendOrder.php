<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\BackendOrder;

class PromotionBackendOrder extends BackendOrder
{
    #[\Override]
    public function getLineItems()
    {
        return $this->getLineItemsFromTable('PromotionBackendOrderLineItem');
    }
}
