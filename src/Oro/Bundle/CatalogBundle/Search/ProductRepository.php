<?php

namespace Oro\Bundle\CatalogBundle\Search;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

/**
 * Website search engine repository for OroProductBundle:Product entity
 * This repository encapsulates Category related operations
 */
class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @param Category|null $category
     * @param SearchQueryInterface $searchQuery
     *
     * @return array ['<categoryId>' => <numberOfProducts>, ...]
     */
    public function getCategoryCountsByCategory(Category $category, SearchQueryInterface $searchQuery = null)
    {
        // calculate counts of products per category
        $counts = $this->getCategoryCounts($searchQuery);

        return $this->normalizeCounts($counts, $category->getMaterializedPath());
    }

    /**
     * @param Category[] $categories
     *
     * @return array ['<categoryId>' => <numberOfProducts>, ...]
     */
    public function getCategoriesCounts(array $categories)
    {
        $query = $this->createQuery()
            ->setFrom('oro_product_'. WebsiteIdPlaceholder::NAME);

        $criteria = array_map(
            function (Category $category) {
                return Criteria::expr()->exists('integer.category_paths.'. $category->getMaterializedPath());
            },
            $categories
        );

        if ($criteria) {
            $query->addWhere(Criteria::expr()->orX(...$criteria), AbstractSearchQuery::WHERE_OR);
        }

        $counts = (array) $this->getCategoryCounts($query);

        $data = [];
        foreach ($categories as $category) {
            $categoryId = $category->getId();
            $path = $category->getMaterializedPath();
            $data[$categoryId] = $counts[$path] ?? 0;
            $data[$categoryId] += array_sum($this->normalizeCounts($counts, $path));
        }

        return $data;
    }

    /**
     * @param SearchQueryInterface $query
     *
     * @return array ['<materializedPath>' => <numberOfProducts>, ...]
     */
    protected function getCategoryCounts(SearchQueryInterface $query = null)
    {
        if (!$query) {
            $query = $this->createQuery();
        } else {
            $query = clone $query;
        }

        // reset query parts to make it work as fast as possible
        $query->getQuery()->select([]);
        $query->getQuery()->getCriteria()->orderBy([]);
        $query->setFirstResult(0);
        $query->setMaxResults(1);

        // calculate category counts
        $query->addAggregate('categoryCounts', 'text.category_path', Query::AGGREGATE_FUNCTION_COUNT);
        $aggregatedData = $query->getResult()->getAggregatedData();

        return $aggregatedData['categoryCounts'] ?? [];
    }

    /**
     * @param array $counts
     * @param string $rootCategoryPath
     *
     * @return array
     */
    protected function normalizeCounts(array $counts, $rootCategoryPath)
    {
        $rootCategoryPath .= '_';
        $normalizedCounts = [];

        $counts = array_filter(
            $counts,
            function ($path) use ($rootCategoryPath) {
                return str_starts_with($path, $rootCategoryPath);
            },
            ARRAY_FILTER_USE_KEY
        );

        foreach ($counts as $path => $count) {
            $path = preg_replace("/^$rootCategoryPath/", '', $path);
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
