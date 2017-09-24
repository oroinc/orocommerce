<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;

class TimeInTransitCacheProviderFactory implements TimeInTransitCacheProviderFactoryInterface
{
    /**
     * @internal
     */
    const CACHE_NAMESPACE_PREFIX = 'oro_ups_time_in_transit';

    /**
     * @var CacheProvider[]
     */
    private $cacheProviderInstances = [];

    /**
     * @var CacheProvider
     */
    private $cacheProviderPrototype;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProviderPrototype = $cacheProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function createCacheProviderForTransport($transportId)
    {
        if (!array_key_exists($transportId, $this->cacheProviderInstances)) {
            $cacheProvider = clone $this->cacheProviderPrototype;
            $cacheProvider->setNamespace(sprintf('%s_%d', self::CACHE_NAMESPACE_PREFIX, $transportId));

            $this->cacheProviderInstances[$transportId] = new TimeInTransitCacheProvider($cacheProvider);
        }

        return $this->cacheProviderInstances[$transportId];
    }
}
