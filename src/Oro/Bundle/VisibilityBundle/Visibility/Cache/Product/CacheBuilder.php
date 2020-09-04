<?php

namespace Oro\Bundle\VisibilityBundle\Visibility\Cache\Product;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\CompositeCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\ProductCaseCacheBuilderInterface;

/**
 * Composite visibility cache builder for Product entity.
 */
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

    /**
     * {@inheritdoc}
     */
    public function productsCategoryChangedWithDisabledReindex(array $products): void
    {
        $this->toggleBuildersReindex(false);

        foreach ($products as $product) {
            $this->productCategoryChanged($product);
        }

        $this->toggleBuildersReindex(true);
    }

    /**
     * @param bool $isReindexEnabled
     */
    private function toggleBuildersReindex(bool $isReindexEnabled): void
    {
        foreach ($this->builders as $builder) {
            if (is_callable([$builder, 'toggleReindex'])) {
                $builder->toggleReindex($isReindexEnabled);
            }
        }
    }
}
