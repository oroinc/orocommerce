<?php

namespace Oro\Bundle\CatalogBundle\Search;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @param SearchQueryInterface $query
     * @return array ['<materializedPath>' => <numberOfProducts>, ...]
     */
    public function getCategoryCounts(SearchQueryInterface $query)
    {
        # reset query parts to make it work as fast as possible
        $query->getQuery()->select([]);
        $query->getQuery()->getCriteria()->orderBy([]);
        $query->setFirstResult(0);
        $query->setMaxResults(1);

        # calculate category counts
        $query->addAggregate('categoryCounts', 'text.category_path', Query::AGGREGATE_FUNCTION_COUNT);
        $aggregatedData = $query->getResult()->getAggregatedData();

        return $aggregatedData['categoryCounts'];
    }
}
