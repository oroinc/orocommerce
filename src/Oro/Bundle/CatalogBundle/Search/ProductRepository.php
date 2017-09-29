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
        $query->addGroupBy('categoryCounts', 'text.category_path', Query::GROUP_FUNCTION_COUNT);
        $groupedData = $query->getResult()->getGroupedData();

        return $groupedData['categoryCounts'];
    }
}
