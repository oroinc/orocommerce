<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Layout data provider which provides count of products per category.
 */
class CategoriesProductsProvider
{
    private ManagerRegistry $doctrine;
    private ProductRepository $searchRepository;
    private CacheInterface $cache;
    private int $cacheLifeTime;

    public function __construct(ManagerRegistry $doctrine, ProductRepository $searchRepository)
    {
        $this->doctrine = $doctrine;
        $this->searchRepository = $searchRepository;
    }

    public function setCache(CacheInterface $cache, $lifeTime = 0) : void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    public function getCountByCategories(array $categoriesIds) : array //[category id => number of products, ...]
    {
        $cacheKey = 'categories_products_' . implode('_', $categoriesIds);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($categoriesIds) {
            $item->expiresAfter($this->cacheLifeTime);
            $categories = $this->doctrine->getManagerForClass(Category::class)
                ->getRepository(Category::class)
                ->findBy(['id' => $categoriesIds]);
            return $this->searchRepository->getCategoriesCounts($categories);
        });
    }
}
