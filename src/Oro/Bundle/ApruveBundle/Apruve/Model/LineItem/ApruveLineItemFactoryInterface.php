<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\LineItem;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

interface ApruveLineItemFactoryInterface
{
    /**
     * @param OrderLineItem $lineItem
     *
     * @return ApruveLineItem
     */
    public function createFromOrderLineItem(OrderLineItem $lineItem);
}
