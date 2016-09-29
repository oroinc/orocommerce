<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;
use Oro\Bundle\OrderBundle\Entity\Order;

interface OrderTotalProviderInterface
{
    const TOTAL_CALC_B2B_TAX_PER_ITEM = '3';
    const PAY_TYPE_INVOICE = '1';
    const FIELD_AMOUNT = 'amount';
    const FIELD_CURRENCY = 'currency';

    /**
     * @param Order $order
     *
     * @return OrderTotal
     */
    public function getOrderTotal(Order $order);
}
