<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * The decorator that saves related products loaded via the decorated provider to a cache
 * and uses this cache to get the related products when they are requested the next time.
 */
class RelatedItemDataProviderCacheDecorator implements RelatedItemDataProviderInterface
{
    /** @var RelatedItemDataProviderInterface */
    private $dataProvider;

    /** @var Cache */
    private $cache;

    /** @var string */
    private $cacheKey;

    public function __construct(RelatedItemDataProviderInterface $dataProvider, Cache $cache, string $cacheKey)
    {
        $this->dataProvider = $dataProvider;
        $this->cache = $cache;
        $this->cacheKey = $cacheKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getRelatedItems(Product $product)
    {
        $cacheKey = sprintf($this->cacheKey, $product->getId());
        $relatedItems = $this->cache->fetch($cacheKey);
        if (false === $relatedItems) {
            $relatedItems = $this->dataProvider->getRelatedItems($product);
            $this->cache->save($cacheKey, $relatedItems);
        }

        return $relatedItems;
    }

    /**
     * {@inheritdoc}
     */
    public function isSliderEnabled()
    {
        return $this->dataProvider->isSliderEnabled();
    }

    /**
     * {@inheritdoc}
     */
    public function isAddButtonVisible()
    {
        return $this->dataProvider->isAddButtonVisible();
    }
}
