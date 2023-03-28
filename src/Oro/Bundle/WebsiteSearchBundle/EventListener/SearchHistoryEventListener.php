<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\SearchResultHistoryManagerInterface;

/**
 * Log search term to search history table basing on the search query.
 */
class SearchHistoryEventListener implements SearchHistoryEventListenerInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    protected const EMPTY_TYPE = 'empty';
    protected const UNKNOWN_TYPE = 'unknown';

    protected SearchResultHistoryManagerInterface $searchResultHistoryManager;

    protected array $supportedQueryTypes = [];

    public function __construct(
        SearchResultHistoryManagerInterface $searchResultHistoryManager
    ) {
        $this->searchResultHistoryManager = $searchResultHistoryManager;
    }

    public function addSupportedSearchQueryType(string $type): void
    {
        $this->supportedQueryTypes[$type] = true;
    }

    public function onSearchAfter(AfterSearchEvent $event): void
    {
        $query = $event->getQuery();
        if (!$this->isSupportedQuery($query)) {
            return;
        }

        $searchTerm = $this->getSearchTerm($query);
        if (!$searchTerm) {
            return;
        }

        $result = $event->getResult();
        $recordsCount = $result->getRecordsCount();
        $this->searchResultHistoryManager->saveSearchResult(
            $searchTerm,
            $this->getSearchType($query, $recordsCount),
            $recordsCount,
            $this->getSearchSessionId($query)
        );
    }

    protected function isSupportedQuery(Query $query): bool
    {
        return $this->isFeaturesEnabled()
            && !empty($this->supportedQueryTypes[$query->getHint(Query::HINT_SEARCH_TYPE)]);
    }

    protected function getSearchTerm(Query $query): ?string
    {
        return $query->getHint(Query::HINT_SEARCH_TERM) ?: null;
    }

    protected function getSearchType(Query $query, int $recordsCount): string
    {
        if ($recordsCount === 0) {
            return self::EMPTY_TYPE;
        }

        if (!$query->hasHint(Query::HINT_SEARCH_TYPE)) {
            return self::UNKNOWN_TYPE;
        }

        return $query->getHint(Query::HINT_SEARCH_TYPE);
    }

    protected function getSearchSessionId(Query $query): ?string
    {
        return $query->getHint(Query::HINT_SEARCH_SESSION) ?: null;
    }
}
