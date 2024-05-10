<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered when suggestions were deleted from the database
 */
final class SuggestionDeleteEvent extends Event
{
    private array $deletedSuggestionIds;

    /**
     * @return array<int>
     */
    public function getDeletedSuggestionIds(): array
    {
        return $this->deletedSuggestionIds;
    }

    /**
     * @param array<int> $deletedSuggestionIds
     *
     * @return void
     */
    public function setDeletedSuggestionIds(array $deletedSuggestionIds): void
    {
        $this->deletedSuggestionIds = $deletedSuggestionIds;
    }
}
