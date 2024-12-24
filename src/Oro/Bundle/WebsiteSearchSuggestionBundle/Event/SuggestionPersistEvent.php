<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered when suggestions persisted to database via sql plain query
 */
final class SuggestionPersistEvent extends Event
{
    /**
     * @var array<int>
     */
    private array $persistedSuggestionIds = [];

    /**
     * @return array<int>
     */
    public function getPersistedSuggestionIds(): array
    {
        return $this->persistedSuggestionIds;
    }

    /**
     * @param array<int> $persistedSuggestionIds
     *
     * @return void
     */
    public function setPersistedSuggestionIds(array $persistedSuggestionIds): void
    {
        $this->persistedSuggestionIds = $persistedSuggestionIds;
    }
}
