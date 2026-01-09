<?php

namespace Oro\Bundle\ProductBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to allow listeners to apply restrictions to product variant queries.
 *
 * This event provides access to the QueryBuilder for product variant queries, enabling listeners to filter
 * which product variants are available based on business rules, configuration, or contextual requirements.
 */
class RestrictProductVariantEvent extends Event
{
    public const NAME = 'oro_product.event.restrict_product_variant_event';

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
