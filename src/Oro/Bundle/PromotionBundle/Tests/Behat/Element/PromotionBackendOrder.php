<?php

namespace Oro\Bundle\PromotionBundle\Tests\Behat\Element;

use Oro\Bundle\OrderBundle\Tests\Behat\Element\BackendOrder;

class PromotionBackendOrder extends BackendOrder
{
    /**
     * {@inheritdoc}
     */
    public function getLineItems()
    {
        return $this->getLineItemsFromTable('PromotionBackendOrderLineItem');
    }
}
