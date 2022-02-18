<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Implementation for time in transit cache provider
 */
class TimeInTransitCacheProvider implements TimeInTransitCacheProviderInterface
{
    private const CACHE_LIFETIME = 86400;
    private const PICKUP_DATE_CACHE_KEY_FORMAT = 'YmdH';

    private UPSSettings $settings;
    private CacheItemPoolInterface $cacheProvider;
    private LifetimeProviderInterface $lifetimeProvider;

    public function __construct(
        UPSSettings $settings,
        CacheItemPoolInterface $cacheProvider,
        LifetimeProviderInterface $lifetimeProvider
    ) {
        $this->settings = $settings;
        $this->cacheProvider = $cacheProvider;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    public function contains(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) : bool {
        return $this->cacheProvider->hasItem($this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate));
    }

    public function fetch(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) : TimeInTransitResultInterface|null {
        $cacheItem = $this->cacheProvider->getItem(
            $this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate)
        );
        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function delete(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) : bool {
        return $this->cacheProvider->deleteItem($this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate));
    }

    public function deleteAll() : bool
    {
        return $this->cacheProvider->clear();
    }

    public function save(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        TimeInTransitResultInterface $timeInTransitResult,
        $lifeTime = self::CACHE_LIFETIME
    ) : bool {
        $cacheItem = $this->cacheProvider->getItem(
            $this->composeCacheKey($shipFromAddress, $shipToAddress, $pickupDate)
        );
        $cacheItem->expiresAfter($this->lifetimeProvider->getLifetime($this->settings, $lifeTime))
            ->set($timeInTransitResult);

        return $this->cacheProvider->save($cacheItem);
    }

    protected function composeCacheKey(
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate
    ) : string {
        $key = sprintf(
            '%s:%s:%s:%s:%s',
            $shipFromAddress->getCountryIso2(),
            $shipFromAddress->getPostalCode(),
            $shipToAddress->getCountryIso2(),
            $shipToAddress->getPostalCode(),
            $pickupDate->format(self::PICKUP_DATE_CACHE_KEY_FORMAT)
        );

        return $this->lifetimeProvider->generateLifetimeAwareKey($this->settings, $key);
    }
}
