<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Component\Cache\Layout\DataProviderCacheTrait;

/**
 * Layout data provider which provides count of products per category.
 */
class CategoriesProductsProvider
{
    use DataProviderCacheTrait;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var ProductRepository
     */
    protected $searchRepository;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository  $searchRepository
     */
    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $searchRepository
    ) {
        $this->searchRepository = $searchRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $categoriesIds
     *
     * @return array
     */
    public function getCountByCategories($categoriesIds)
    {
        $this->initCache(['categories_products', implode('_', $categoriesIds)]);

        $useCache = $this->isCacheUsed();
        if (true === $useCache) {
            $result = $this->getFromCache();
            if (false !== $result) {
                return $result;
            }
        }

        $categories = $this->categoryRepository->findBy(['id' => $categoriesIds]);
        $result = $this->searchRepository->getCategoriesCounts($categories);

        if (true === $useCache) {
            $this->saveToCache($result);
        }

        return $result;
    }
}
