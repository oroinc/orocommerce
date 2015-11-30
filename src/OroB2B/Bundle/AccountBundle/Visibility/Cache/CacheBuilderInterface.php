<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

interface CacheBuilderInterface
{
    /**
     * @param object $visibilitySettings
     */
    public function resolveVisibilitySettings($visibilitySettings);

    /**
     * @param object $visibilitySettings
     * @return mixed
     */
    public function isVisibilitySettingsSupported($visibilitySettings);

    /**
     * @param Category $category
     * @return mixed
     */
    public function updateResolvedVisibilityByCategory(Category $category);

    /**
     * @param Product $product
     * @return mixed
     */
    public function updateProductResolvedVisibility(Product $product);

    /**
     * @param Website|null $website
     * @return mixed
     */
    public function buildCache(Website $website = null);
}
