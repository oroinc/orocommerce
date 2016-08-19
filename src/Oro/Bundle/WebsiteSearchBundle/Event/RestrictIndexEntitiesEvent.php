<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Doctrine\ORM\QueryBuilder;

class RestrictIndexEntitiesEvent
{
    const NAME = 'oro_website_search.event.restrict_index_entities';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string */
    protected $entityClassname;

    /** @var array */
    protected $context;

    /**
     * @param QueryBuilder $qb
     * @param string $entityClassname
     * @param array $context
     */
    public function __construct(QueryBuilder $qb, $entityClassname, array $context)
    {
        $this->queryBuilder = $qb;
        $this->entityClassname = $entityClassname;
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
    public function getEntityClassname()
    {
        return $this->entityClassname;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
