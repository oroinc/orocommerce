<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

class RestrictIndexEntitiesEvent extends Event
{
    const NAME = 'oro_website_search.event.restrict_index_entities';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $entityClass;

    /** @var array */
    protected $context;

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param array $context
     */
    public function __construct(QueryBuilder $qb, $entityClass, array $context)
    {
        $this->queryBuilder = $qb;
        $this->entityClass = $entityClass;
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
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
