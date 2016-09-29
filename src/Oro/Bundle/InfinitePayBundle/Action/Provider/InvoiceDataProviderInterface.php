<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData;
use Oro\Bundle\OrderBundle\Entity\Order;

interface InvoiceDataProviderInterface
{
    /**
     * @param Order $order
     * @param int   $delayInDays
     *
     * @return InvoiceData
     */
    public function getInvoiceData(Order $order, $delayInDays = 0);
}
