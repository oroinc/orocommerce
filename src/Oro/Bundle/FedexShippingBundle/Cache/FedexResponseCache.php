<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache adapter for storing responses from FedEx service
 */
class FedexResponseCache implements FedexResponseCacheInterface
{
    private const LIFETIME = 86400;
    private CacheItemPoolInterface $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function has(FedexResponseCacheKeyInterface $key): bool
    {
        return $this->cache->getItem($this->generateCacheKey($key))->isHit();
    }

    public function get(FedexResponseCacheKeyInterface $key) : FedexRateServiceResponseInterface|null
    {
        $cacheKey = $this->cache->getItem($this->generateCacheKey($key));
        return $cacheKey->isHit() ? $cacheKey->get() : null;
    }

    public function set(FedexResponseCacheKeyInterface $key, FedexRateServiceResponseInterface $response): bool
    {
        $cacheItem = $this->cache->getItem($this->generateCacheKey($key));
        $cacheItem->expiresAfter($this->getInvalidateAt($key->getSettings()))->set($response);
        return $this->cache->save($cacheItem);
    }

    public function delete(FedexResponseCacheKeyInterface $key): bool
    {
        return $this->cache->deleteItem($this->generateCacheKey($key));
    }

    public function deleteAll(): bool
    {
        return $this->cache->clear();
    }

    private function getInvalidateAt(FedexIntegrationSettings $settings): int
    {
        $interval = 0;

        $invalidateAt = $settings->getInvalidateCacheAt();
        if ($invalidateAt) {
            $interval = $invalidateAt->getTimestamp() - time();
        }

        if ($interval <= 0) {
            $interval = static::LIFETIME;
        }

        return $interval;
    }

    private function generateCacheKey(FedexResponseCacheKeyInterface $key) : string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            $key->getCacheKey() . '_' .  $key->getSettings()->getId()
        );
    }
}
