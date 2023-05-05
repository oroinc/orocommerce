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

    public function getPrice(ShippingContextInterface $context, string $methodId, string $typeId, int $ruleId): ?Price
    {
        $cacheKey = $this->generateKey($context, $methodId, $typeId, $ruleId);
        $cacheItem = $this->cache->getItem($cacheKey);

        return $cacheItem->isHit() ? $cacheItem->get() : null;
    }

    public function hasPrice(ShippingContextInterface $context, string $methodId, string $typeId, int $ruleId) : bool
    {
        return $this->cache->getItem($this->generateKey($context, $methodId, $typeId, $ruleId))->isHit();
    }

    public function savePrice(
        ShippingContextInterface $context,
        string $methodId,
        string $typeId,
        int $ruleId,
        Price $price
    ) : void {
        $cacheKey = $this->generateKey($context, $methodId, $typeId, $ruleId);
        $cacheItem = $this->cache->getItem($cacheKey);
        $cacheItem->set($price)->expiresAfter(static::CACHE_LIFETIME);
        $this->cache->save($cacheItem);
    }

    /**
     * @param ShippingContextInterface $context
     * @param string[] $identifiers
     * @return string
     */
    private function generateKey(ShippingContextInterface $context, ...$identifiers): string
    {
        array_unshift($identifiers, $this->cacheKeyGenerator->generateKey($context));
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            implode("|", $identifiers)
        );
    }

    public function deleteAllPrices() : void
    {
        $this->cache->clear();
    }
}
