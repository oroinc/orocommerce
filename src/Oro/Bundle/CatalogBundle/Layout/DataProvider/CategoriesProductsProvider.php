<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
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
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @param ProductRepository $searchRepository
     */
    public function __construct(ManagerRegistry $registry, ProductRepository $searchRepository)
    {
        $this->registry = $registry;
        $this->searchRepository = $searchRepository;
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
            if ($result) {
                return $result;
            }
        }

        $categories = $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class)
            ->findBy(['id' => $categoriesIds]);

        $result = $this->searchRepository->getCategoriesCounts($categories);

        if (true === $useCache) {
            $this->saveToCache($result);
        }

        return $result;
    }
}
