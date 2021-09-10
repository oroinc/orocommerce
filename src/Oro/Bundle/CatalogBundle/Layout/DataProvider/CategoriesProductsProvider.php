<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;

/**
 * Layout data provider which provides count of products per category.
 */
class CategoriesProductsProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ProductRepository */
    private $searchRepository;

    /** @var CacheProvider */
    private $cache;

    /** @var int */
    private $cacheLifeTime;

    public function __construct(ManagerRegistry $doctrine, ProductRepository $searchRepository)
    {
        $this->doctrine = $doctrine;
        $this->searchRepository = $searchRepository;
    }

    /**
     * @param CacheProvider $cache
     * @param int           $lifeTime
     */
    public function setCache(CacheProvider $cache, $lifeTime = 0)
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @param int[] $categoriesIds
     *
     * @return array [category id => number of products, ...]
     */
    public function getCountByCategories($categoriesIds)
    {
        $cacheKey = 'categories_products_' . implode('_', $categoriesIds);

        $result = $this->cache->fetch($cacheKey);
        if (false !== $result) {
            return $result;
        }

        $categories = $this->doctrine->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->findBy(['id' => $categoriesIds]);

        $result = $this->searchRepository->getCategoriesCounts($categories);
        $this->cache->save($cacheKey, $result, $this->cacheLifeTime);

        return $result;
    }
}
