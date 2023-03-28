<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\SearchBundle\Event\SearchQueryAwareEventInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event which is triggered after search query is executed.
 */
class AfterSearchEvent extends Event implements SearchQueryAwareEventInterface
{
    public const EVENT_NAME = 'oro_website_search.after_search';

    private Result $result;
    private Query $query;
    private array $context;

    public function __construct(Result $result, Query $query, array $context = [])
    {
        $this->result = $result;
        $this->query = $query;
        $this->context = $context;
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
