<?php

namespace Oro\Bundle\ProductBundle\Provider\Cache\Doctrine;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;

/**
 * The decorator that saves products loaded via the decorated provider to a cache
 * and uses this cache to get the products when they are requested the next time.
 */
class CachedProductsProviderDecorator implements ProductsProviderInterface
{
    /**
     * @var ProductsProviderInterface
     */
    private $decoratedProvider;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var string
     */
    private $cacheKey;

    public function __construct(ProductsProviderInterface $decoratedProvider, Cache $cache, string $cacheKey)
    {
        $this->decoratedProvider = $decoratedProvider;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * {@inheritDoc}
     */
    public function getProducts()
    {
        $products = $this->cache->fetch($this->cacheKey);
        if (false === $products) {
            $products = $this->decoratedProvider->getProducts();
            $this->cache->save($this->cacheKey, $products);
        }

        return $products;
    }
}
