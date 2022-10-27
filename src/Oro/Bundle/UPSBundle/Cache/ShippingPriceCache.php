<?php

namespace Oro\Bundle\UPSBundle\Cache;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Implementation for shipping price cache provider
 */
class ShippingPriceCache
{
    private const LIFETIME = 86400;

    private CacheItemPoolInterface $cache;
    private LifetimeProviderInterface $lifetimeProvider;

    public function __construct(CacheItemPoolInterface $cache, LifetimeProviderInterface $lifetimeProvider)
    {
        $this->cache = $cache;
        $this->lifetimeProvider = $lifetimeProvider;
    }

    public function containsPrice(ShippingPriceCacheKey $key) : bool
    {
        return $this->cache->hasItem($this->generateStringKey($key));
    }

    public function fetchPrice(ShippingPriceCacheKey $key) : bool|Price
    {
        $cacheItem = $this->cache->getItem($this->generateStringKey($key));
        return $cacheItem->isHit() ? $cacheItem->get() : false;
    }

    public function savePrice(ShippingPriceCacheKey $key, Price $price) : bool
    {
        $cacheItem = $this->cache->getItem($this->generateStringKey($key));
        $lifetime = $this->lifetimeProvider->getLifetime($key->getTransport(), static::LIFETIME);
        $cacheItem->expiresAfter($lifetime)->set($price);
        return $this->cache->save($cacheItem);
    }

    public function deleteAll() : void
    {
        $this->cache->clear();
    }

    public function createKey(
        UPSTransport $transport,
        PriceRequest $priceRequest,
        string|null $methodId,
        string|null $typeId
    ) : ShippingPriceCacheKey {
        return (new ShippingPriceCacheKey())->setTransport($transport)->setPriceRequest($priceRequest)
            ->setMethodId($methodId)->setTypeId($typeId);
    }

    protected function generateStringKey(ShippingPriceCacheKey $key) : string
    {
        return $this->lifetimeProvider->generateLifetimeAwareKey($key->getTransport(), $key->generateKey());
    }
}
