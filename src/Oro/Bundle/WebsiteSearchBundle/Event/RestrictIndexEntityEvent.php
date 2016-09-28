<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

class RestrictIndexEntityEvent extends Event
{
    const NAME = 'oro_website_search.event.restrict_index_entity';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var array */
    protected $context;

    /**
     * @param QueryBuilder $qb
     * @param array $context
     * @param array $context
     */
    public function __construct(QueryBuilder $qb, array $context)
    {
        $this->queryBuilder = $qb;
        $this->context = $context;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
