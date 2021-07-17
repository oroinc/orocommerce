<?php

namespace Oro\Bundle\ProductBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

class RestrictProductVariantEvent extends Event
{
    const NAME = 'oro_product.event.restrict_product_variant_event';

    /** @var QueryBuilder */
    protected $queryBuilder;

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
