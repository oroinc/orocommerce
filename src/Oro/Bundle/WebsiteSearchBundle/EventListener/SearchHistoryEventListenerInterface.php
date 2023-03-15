<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;

/**
 * Log search term to search history table basing on the search query.
 */
interface SearchHistoryEventListenerInterface
{
    public function addSupportedSearchQueryType(string $type): void;

    public function onSearchAfter(AfterSearchEvent $event): void;
}
