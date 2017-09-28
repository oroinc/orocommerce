<?php

namespace Oro\Bundle\CatalogBundle\Search;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Engine\GroupingSupportedEngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /** @var EngineInterface */
    private $websiteSearchEngine;

    /**
     * @param EngineInterface $searchEngine
     */
    public function setWebsiteSearchEngine(EngineInterface $searchEngine)
    {
        $this->websiteSearchEngine = $searchEngine;
    }

    /**
     * @param SearchQueryInterface $query
     * @return array|null ['<materializedPath>' => <numberOfProducts>, ...]
     */
    public function getCategoryCounts(SearchQueryInterface $query)
    {
        if (!$this->websiteSearchEngine instanceof GroupingSupportedEngineInterface) {
            return null;
        }

        # reset query parts to make it as fast as possible
        $query->setFirstResult(0);
        $query->setMaxResults(1);
        $query->addGroupBy('categoryCounts', 'text.category_path', Query::GROUP_FUNCTION_COUNT);

        $groupedData = $query->getResult()->getGroupedData();
        if (array_key_exists('categoryCounts', $groupedData)) {
            return $groupedData['categoryCounts'];
        }

        return null;
    }
}
