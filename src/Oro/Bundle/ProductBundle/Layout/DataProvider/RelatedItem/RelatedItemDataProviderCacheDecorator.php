<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider\RelatedItem;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\ProductBundle\Entity\Product;

class RelatedItemDataProviderCacheDecorator implements RelatedItemDataProviderInterface
{
    /** @var RelatedItemDataProviderInterface */
    private $dataProvider;

    /** @var Cache */
    private $cache;

    /** @var string */
    private $cacheKey;

    /**
     * @param RelatedItemDataProviderInterface $dataProvider
     * @param Cache                            $cache
     * @param string                           $cacheKey
     */
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
        if ($this->cache->contains($cacheKey)) {
            return $this->cache->fetch($cacheKey);
        }

        $relatedItems = $this->dataProvider->getRelatedItems($product);
        $this->cache->save($cacheKey, $relatedItems);

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
