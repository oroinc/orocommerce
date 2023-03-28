<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\EventListener\SearchHistoryEventListener;

/**
 * Create SearchResult basing on the search results.
 * Get search term from request.
 */
class ProductSearchHistoryEventListener extends SearchHistoryEventListener
{
    private SearchProductHandler $searchProductHandler;

    public function setSearchProductHandler(SearchProductHandler $searchProductHandler): void
    {
        $this->searchProductHandler = $searchProductHandler;
    }

    protected function getSearchTerm(Query $query): ?string
    {
        return $this->searchProductHandler->getSearchString() ?: parent::getSearchTerm($query);
    }
}
