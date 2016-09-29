<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\DebtorData;
use Oro\Bundle\OrderBundle\Entity\Order;

interface DebtorDataProviderInterface
{
    /**
     * @param Order $order
     *
     * @return DebtorData
     */
    public function getDebtorData(Order $order);
}
