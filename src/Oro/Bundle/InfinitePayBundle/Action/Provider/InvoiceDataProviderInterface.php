<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData;
use Oro\Bundle\OrderBundle\Entity\Order;

interface InvoiceDataProviderInterface
{
    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param int $delayInDays
     * @return InvoiceData
     */
    public function getInvoiceData(Order $order, InfinitePayConfigInterface $config, $delayInDays = 0);
}
