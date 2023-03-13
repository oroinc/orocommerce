<?php

namespace Oro\Bundle\WebsiteSearchBundle\Manager;

/**
 * Manipulates search result tracking.
 */
interface SearchResultHistoryManagerInterface
{
    public function saveSearchResult(
        string $searchTerm,
        string $searchType,
        int $resultsCount,
        string $searchSessionId = null
    ): void;

    public function removeOutdatedHistoryRecords(): void;

    public function actualizeHistoryReport(): void;
}
