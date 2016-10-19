<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\SearchBundle\Query\Query;
use Symfony\Component\EventDispatcher\Event;


/**
 * This event is fired by the Product Manager when
 * search query restriction should be applied.
 * The listeners of this event should modify the inner
 * query to apply additional conditions.
 */
class ProductSearchQueryRestrictionEvent extends Event
{
    const NAME = 'oro_product.product_search_query.restriction';

    /**
     * @var Query
     */
    private $query;

    /**
     * @param SearchQueryInterface $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
