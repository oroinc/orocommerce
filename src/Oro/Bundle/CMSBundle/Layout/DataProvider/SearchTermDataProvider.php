<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Oro\Bundle\WebsiteSearchTermBundle\Provider\SearchTermProvider;

/**
 * Layout data provider for Search Terms.
 */
class SearchTermDataProvider
{
    public function __construct(
        private SearchTermProvider $searchTermProvider
    ) {
    }

    public function getSearchTermContentBlockAlias(string $search): ?string
    {
        $searchTerm = $this->searchTermProvider->getMostSuitableSearchTerm($search);

        return $searchTerm?->getContentBlock()?->getAlias();
    }
}
