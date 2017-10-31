<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

interface TimeInTransitCacheProviderFactoryInterface
{
    /**
     * @param int $transportId
     *
     * @return TimeInTransitCacheProviderInterface
     */
    public function createCacheProviderForTransport($transportId);
}
