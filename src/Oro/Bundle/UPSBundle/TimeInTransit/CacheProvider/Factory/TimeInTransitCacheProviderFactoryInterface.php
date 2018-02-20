<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

interface TimeInTransitCacheProviderFactoryInterface
{
    /**
     * @param UPSSettings $settings
     *
     * @return TimeInTransitCacheProviderInterface
     */
    public function createCacheProviderForTransport(UPSSettings $settings): TimeInTransitCacheProviderInterface;
}
