<?php

namespace Oro\Bundle\ProductBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

class RestrictFrontendVisibilityEvent extends Event
{
    const NAME = 'oro_product.event.restrict_frontend_visibility';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /**
     * @param QueryBuilder $qb
     */
    public function __construct(QueryBuilder $qb)
    {
        $this->queryBuilder = $qb;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
}
