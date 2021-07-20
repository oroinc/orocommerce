<?php

namespace Oro\Bundle\ShippingBundle\Provider\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * The cache for shipping prices.
 */
class ShippingPriceCache
{
    /**
     * 1 hour, 60 * 60
     */
    const CACHE_LIFETIME = 3600;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var ShippingContextCacheKeyGenerator
     */
    protected $cacheKeyGenerator;

    public function __construct(
        CacheProvider $cacheProvider,
        ShippingContextCacheKeyGenerator $cacheKeyGenerator
    ) {
        $this->cache = $cacheProvider;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @return Price|null
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        $key = $this->generateKey($context, $methodId, $typeId);
        $value = $this->cache->fetch($key);

        return false !== $value ? $value : null;
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @return bool
     */
    public function hasPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        return $this->cache->contains($this->generateKey($context, $methodId, $typeId));
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @param Price $price
     * @return $this
     */
    public function savePrice(ShippingContextInterface $context, $methodId, $typeId, Price $price)
    {
        $key = $this->generateKey($context, $methodId, $typeId);
        $this->cache->save($key, $price, static::CACHE_LIFETIME);
        return $this;
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @return string
     */
    protected function generateKey(ShippingContextInterface $context, $methodId, $typeId)
    {
        return $this->cacheKeyGenerator->generateKey($context).$methodId.$typeId;
    }

    public function deleteAllPrices()
    {
        $this->cache->deleteAll();
    }
}
