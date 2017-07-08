<?php

namespace Oro\Bundle\ProductBundle\Provider\Cache\Doctrine;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ProductBundle\Provider\ProductsProviderInterface;

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

    /**
     * @param ProductsProviderInterface $decoratedProvider
     * @param Cache                     $cache
     * @param string                    $cacheKey
     */
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
        if ($this->cache->contains($this->cacheKey)) {
            return $this->cache->fetch($this->cacheKey);
        }

        $products = $this->decoratedProvider->getProducts();
        $this->cache->save($this->cacheKey, $products);

        return $products;
    }
}
