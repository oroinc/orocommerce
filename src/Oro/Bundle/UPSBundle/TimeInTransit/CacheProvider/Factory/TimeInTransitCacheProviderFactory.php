<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

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
     * @var LifetimeProviderInterface
     */
    private $lifetimeProvider;

    public function __construct(CacheProvider $cacheProvider, LifetimeProviderInterface $lifetimeProvider)
    {
        $this->cacheProviderPrototype = $cacheProvider;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    /**
     * {@inheritDoc}
     */
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
        $cacheProvider->setNamespace(sprintf('%s_%d', self::CACHE_NAMESPACE_PREFIX, $settings->getId()));

        return new TimeInTransitCacheProvider($settings, $cacheProvider, $this->lifetimeProvider);
    }
}
