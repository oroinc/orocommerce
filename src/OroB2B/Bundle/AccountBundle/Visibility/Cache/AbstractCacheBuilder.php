<?php

namespace OroB2B\Bundle\AccountBundle\Visibility\Cache;

use Doctrine\ORM\EntityRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

abstract class AbstractCacheBuilder implements CacheBuilderInterface
{
    /**
     * @param object $visibilitySettings
     * @return mixed
     */
    abstract public function resolveVisibilitySettings($visibilitySettings);

    /**
     * @param object $visibilitySettings
     * @return mixed
     */
    abstract public function isVisibilitySettingsSupported($visibilitySettings);

    /**
     * @param Category $category
     * @return mixed
     */
    abstract public function updateResolvedVisibilityByCategory(Category $category);

    /**
     * @param Product $product
     * @return mixed
     */
    abstract public function updateProductResolvedVisibility(Product $product);

    /**
     * @param Website|null $website
     * @return mixed
     */
    abstract public function buildCache(Website $website = null);

    /**
     * @return EntityRepository
     */
    abstract protected function getRepository();

    /**
     * @return int
     */
    protected function clearBeforeBuild()
    {
        return $this->getRepository()
            ->createQueryBuilder('pv')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
