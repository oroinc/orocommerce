<?php

namespace Oro\Bundle\CatalogBundle\Provider;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class CategoryCountsProvider
{
    /** @var ProductRepository */
    protected $productSearchRepository;

    /**
     * @param ProductRepository $productSearchRepository
     */
    public function __construct(ProductRepository $productSearchRepository)
    {
        $this->productSearchRepository = $productSearchRepository;
    }

    /**
     * @param SearchQueryInterface $searchQuery
     * @param Category|null $category
     * @return array
     */
    public function getCategoryCounts(SearchQueryInterface $searchQuery, Category $category = null)
    {
        // calculate counts of products per category
        $counts = $this->productSearchRepository->getCategoryCounts($searchQuery);

        return $this->normalizeCounts(
            $counts,
            $category ? $category->getMaterializedPath() : ''
        );
    }

    /**
     * @param array $counts
     * @param string $rootCategoryPath
     * @return array
     */
    protected function normalizeCounts(array $counts, $rootCategoryPath)
    {
        if ($rootCategoryPath) {
            $rootCategoryPath .= '_';

            $counts = array_filter(
                $counts,
                function ($path) use ($rootCategoryPath) {
                    return strpos($path, $rootCategoryPath) === 0;
                },
                ARRAY_FILTER_USE_KEY
            );
        }
        $normalizedCounts = [];

        foreach ($counts as $path => $count) {
            $path = str_replace($rootCategoryPath, '', $path);
            $pathParts = explode('_', $path);
            $mainCategoryId = reset($pathParts);

            if (!isset($normalizedCounts[$mainCategoryId])) {
                $normalizedCounts[$mainCategoryId] = 0;
            }

            $normalizedCounts[$mainCategoryId] += $count;
        }

        return $normalizedCounts;
    }
}
