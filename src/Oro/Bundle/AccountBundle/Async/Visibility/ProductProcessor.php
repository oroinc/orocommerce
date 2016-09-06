<?php

namespace Oro\Bundle\AccountBundle\Async\Visibility;

use Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class ProductProcessor extends AbstractVisibilityProcessor
{
    /**
     * @param object|Product $entity
     */
    protected function resolveVisibilityByEntity($entity)
    {
        if ($this->cacheBuilder instanceof ProductCaseCacheBuilderInterface) {
            $this->cacheBuilder->productCategoryChanged($entity);
        }
    }
}
