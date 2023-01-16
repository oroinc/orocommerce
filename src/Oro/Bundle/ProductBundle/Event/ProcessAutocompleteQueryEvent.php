<?php
declare(strict_types = 1);

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event used to modify autocomplete query
 */
class ProcessAutocompleteQueryEvent extends Event
{
    public function __construct(protected SearchQueryInterface $query, protected string $queryString)
    {
    }

    public function getQuery(): SearchQueryInterface
    {
        return $this->query;
    }

    public function setQuery(SearchQueryInterface $query): void
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }
}
