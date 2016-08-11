<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query;

class BeforeSearchEvent extends Event
{
    const EVENT_NAME = "oro_website_search.before_search";

    /**
     * @var Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $context;

    /**
     * @param Query $query
     * @param array $context
     */
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
