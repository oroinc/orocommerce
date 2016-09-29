<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;

interface ClientDataProviderInterface
{
    /**
     * @param string $orderId
     *
     * @return ClientData
     */
    public function getClientData($orderId);
}
