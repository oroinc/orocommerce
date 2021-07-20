<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Bundle\SearchBundle\Event\SearchQueryAwareEventInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event which is triggered before search query is executed and gives possibility to adjust search query.
 */
class BeforeSearchEvent extends Event implements SearchQueryAwareEventInterface
{
    const EVENT_NAME = 'oro_website_search.before_search';

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $context;

    public function __construct(Query $query, array $context = [])
    {
        $this->query = $query;
        $this->context = $context;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
