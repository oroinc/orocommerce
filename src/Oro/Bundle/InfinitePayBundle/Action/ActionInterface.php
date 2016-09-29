<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

interface ActionInterface
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Order              $order
     *
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction, Order $order);
}
