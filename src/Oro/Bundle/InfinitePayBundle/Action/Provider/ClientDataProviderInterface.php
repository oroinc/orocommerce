<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;

interface ClientDataProviderInterface
{
    /**
     * @param string $orderId
     *
     * @param InfinitePayConfigInterface $config
     * @return ClientData
     */
    public function getClientData($orderId, InfinitePayConfigInterface $config);
}
