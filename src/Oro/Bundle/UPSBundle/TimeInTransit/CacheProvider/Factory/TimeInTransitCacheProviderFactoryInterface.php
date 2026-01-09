<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

/**
 * Defines the contract for factories that create Time In Transit cache providers.
 *
 * Implementations of this interface create {@see TimeInTransitCacheProviderInterface} instances
 * configured for specific UPS transport settings. This allows for transport-specific cache configurations,
 * including custom cache lifetimes and invalidation strategies.
 */
interface TimeInTransitCacheProviderFactoryInterface
{
    public function createCacheProviderForTransport(UPSSettings $settings): TimeInTransitCacheProviderInterface;
}
