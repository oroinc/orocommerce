<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache\Product;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\AbstractComposeCacheBuilder;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class CacheBuilder extends AbstractComposeCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /**
     * @var ProductCaseCacheBuilderInterface[]
     */
    protected $builders = [];

    /**
     * @param ProductCaseCacheBuilderInterface $cacheBuilder
     */
    public function addBuilder(ProductCaseCacheBuilderInterface $cacheBuilder)
    {
        if (!in_array($cacheBuilder, $this->builders, true)) {
            $this->builders[] = $cacheBuilder;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product)
    {
        foreach ($this->builders as $builder) {
            $builder->productCategoryChanged($product);
        }
    }
}
