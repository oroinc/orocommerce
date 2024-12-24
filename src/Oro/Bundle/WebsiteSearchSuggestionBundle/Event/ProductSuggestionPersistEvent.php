<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered when product suggestion ids (relation) persisted to database via sql plain query
 */
final class ProductSuggestionPersistEvent extends Event
{
    private array $persistedProductSuggestionIds = [];

    /**
     * @return array<int>
     */
    public function getPersistedProductSuggestionIds(): array
    {
        return $this->persistedProductSuggestionIds;
    }

    /**
     * @param array<int> $persistedProductSuggestionIds
     *
     * @return void
     */
    public function setPersistedProductSuggestionIds(array $persistedProductSuggestionIds): void
    {
        $this->persistedProductSuggestionIds = $persistedProductSuggestionIds;
    }
}
