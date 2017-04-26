<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

interface RequestMapperInterface
{
    /**
     * @param Order $order
     * @param InfinitePayConfigInterface $config
     * @param array $userInput
     * @return mixed
     */
    public function createRequestFromOrder(Order $order, InfinitePayConfigInterface $config, array $userInput);
}
