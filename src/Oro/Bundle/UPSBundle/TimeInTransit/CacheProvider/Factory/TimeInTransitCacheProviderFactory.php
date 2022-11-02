<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache factory class. Uses enableVersioning param of Symfony adapter in order to have different settings cached
 */
class TimeInTransitCacheProviderFactory implements TimeInTransitCacheProviderFactoryInterface
{
    private array $cacheProviderInstances = [];
    private CacheItemPoolInterface $cacheProviderPrototype;
    private LifetimeProviderInterface $lifetimeProvider;

    public function __construct(CacheItemPoolInterface $cacheProvider, LifetimeProviderInterface $lifetimeProvider)
    {
        $this->cacheProviderPrototype = $cacheProvider;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    public function createCacheProviderForTransport(UPSSettings $settings): TimeInTransitCacheProviderInterface
    {
        $settingsId = $settings->getId();

        if (!array_key_exists($settingsId, $this->cacheProviderInstances)) {
            $this->cacheProviderInstances[$settingsId] = $this->createCacheProvider($settings);
        }

        return $this->cacheProviderInstances[$settingsId];
    }

    private function createCacheProvider(UPSSettings $settings): TimeInTransitCacheProviderInterface
    {
        $cacheProvider = clone $this->cacheProviderPrototype;
        $cacheProvider->enableVersioning(true);
        return new TimeInTransitCacheProvider($settings, $cacheProvider, $this->lifetimeProvider);
    }
}
