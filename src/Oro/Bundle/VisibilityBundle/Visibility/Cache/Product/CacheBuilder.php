<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Oro\Bundle\VisibilityBundle\Visibility\Cache\CompositeCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

class CacheBuilder extends CompositeCacheBuilder implements ProductCaseCacheBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function productCategoryChanged(Product $product)
    {
        foreach ($this->builders as $builder) {
            if ($builder instanceof ProductCaseCacheBuilderInterface) {
                $builder->productCategoryChanged($product);
            }
        }
    }
}
