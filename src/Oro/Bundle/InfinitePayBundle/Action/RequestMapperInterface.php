<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ReserveOrder;
use Oro\Bundle\OrderBundle\Entity\Order;

interface RequestMapperInterface
{
    /**
     * @param Order $order
     * @param array $userInput
     *
     * @return mixed
     */
    public function createRequestFromOrder(Order $order, array $userInput);
}
