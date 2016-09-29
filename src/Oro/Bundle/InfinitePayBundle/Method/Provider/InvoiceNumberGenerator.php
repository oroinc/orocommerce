<?php

namespace Oro\Bundle\InfinitePayBundle\Method\Provider;

use Oro\Bundle\OrderBundle\Entity\Order;

class InvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    /**
     * @param Order $order
     *
     * @return string
     */
    public function getInvoiceNumberFromOrder(Order $order)
    {
        return $order->getIdentifier();
    }
}
