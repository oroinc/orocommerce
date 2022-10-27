<?php

namespace Oro\Bundle\ShippingBundle\Provider\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The cache for shipping prices.
 */
class ShippingPriceCache
{
    private const CACHE_LIFETIME = 3600;

    private CacheItemPoolInterface $cache;
    private ShippingContextCacheKeyGenerator $cacheKeyGenerator;

    public function __construct(
        CacheItemPoolInterface $cacheProvider,
        ShippingContextCacheKeyGenerator $cacheKeyGenerator
    ) {
        $this->cache = $cacheProvider;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
    }

    public function getPrice(ShippingContextInterface $context, string $methodId, string $typeId) : Price|null
    {
        $cacheKey = $this->generateKey($context, $methodId, $typeId);
        $cacheItem = $this->cache->getItem($cacheKey);

        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function hasPrice(ShippingContextInterface $context, string $methodId, string $typeId) : bool
    {
        return $this->cache->getItem($this->generateKey($context, $methodId, $typeId))->isHit();
    }

    public function savePrice(ShippingContextInterface $context, string $methodId, string $typeId, Price $price) : void
    {
        $cacheKey = $this->generateKey($context, $methodId, $typeId);
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($price)->expiresAfter(static::CACHE_LIFETIME);
        $this->cache->save($cacheItem);
    }

    private function generateKey(ShippingContextInterface $context, string $methodId, string $typeId) : string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            $this->cacheKeyGenerator->generateKey($context).$methodId.$typeId
        );
    }

    public function deleteAllPrices() : void
    {
        $this->cache->clear();
    }
}
