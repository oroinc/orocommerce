<?php

namespace Oro\Bundle\DPDBundle\Transaction\File\Name\Provider;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\OrderBundle\Entity\Order;

interface TransactionFileNameProviderInterface
{
    /**
     * @param Order            $order
     * @param SetOrderResponse $response
     *
     * @return string
     */
    public function getTransactionFileName(Order $order, SetOrderResponse $response);
}
