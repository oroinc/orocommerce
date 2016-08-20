<?php

namespace Oro\Bundle\OrderBundle\Handler;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderTotalsHandler
{
    /**
     * @param TotalProcessorProvider $totalProvider
     * @param LineItemSubtotalProvider $lineItemSubtotalProvider
     */
    public function __construct(
        TotalProcessorProvider $totalProvider,
        LineItemSubtotalProvider $lineItemSubtotalProvider
    ) {
        $this->totalProvider = $totalProvider;
        $this->lineItemSubtotalProvider = $lineItemSubtotalProvider;
    }

    /**
     * @param Order $order
     */
    public function fillSubtotals(Order $order)
    {
        $subtotal = $this->lineItemSubtotalProvider->getSubtotal($order);
        $total = $this->totalProvider->enableRecalculation()->getTotal($order);

        $order->setSubtotal($subtotal->getAmount());
        $order->setTotal($total->getAmount());
    }
}
