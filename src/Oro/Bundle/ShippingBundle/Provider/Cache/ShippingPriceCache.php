<?php

namespace Oro\Bundle\ShippingBundle\Provider\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextCacheKeyGenerator;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ShippingPriceCache
{
    /**
     * 1 hour, 60 * 60
     */
    const CACHE_LIFETIME = 3600;

    const UPDATED_AT_KEY = 'oro_shipping_rule_updated_at';

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ShippingContextCacheKeyGenerator
     */
    protected $cacheKeyGenerator;

    /**
     * @param CacheProvider $cacheProvider
     * @param ManagerRegistry $doctrine
     * @param ShippingContextCacheKeyGenerator $cacheKeyGenerator
     */
    public function __construct(
        CacheProvider $cacheProvider,
        ManagerRegistry $doctrine,
        ShippingContextCacheKeyGenerator $cacheKeyGenerator
    ) {
        $this->cache = $cacheProvider;
        $this->doctrine = $doctrine;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @return Price|bool
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        $key = $this->generateKey($context, $methodId, $typeId);
        if ($this->isShippingRulesUpdated() || !$this->cache->contains($key)) {
            return false;
        }
        return $this->cache->fetch($key);
    }

    /**
     * @param ShippingContextInterface $context
     * @param string $methodId
     * @param string $typeId
     * @return bool
     */
    public function hasPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        return !$this->isShippingRulesUpdated()
            && $this->cache->contains($this->generateKey($context, $methodId, $typeId));
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

    /**
     * @return bool
     */
    protected function isShippingRulesUpdated()
    {
        $updatedAt = $this->doctrine->getManagerForClass('OroShippingBundle:ShippingRule')
            ->getRepository('OroShippingBundle:ShippingRule')->getLastUpdateAt();
        if (!$this->cache->contains(static::UPDATED_AT_KEY)
            || $updatedAt->getTimestamp() > $this->cache->fetch(static::UPDATED_AT_KEY)
        ) {
            $this->cache->deleteAll();
            $this->cache->save(static::UPDATED_AT_KEY, $updatedAt->getTimestamp());
            return true;
        }
        return false;
    }
}
