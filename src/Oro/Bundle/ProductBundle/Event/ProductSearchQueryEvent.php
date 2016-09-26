<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * This event is fired by the Product Manager when
 * search query restriction should be applied.
 * The listeners of this event should modify the inner
 * query to apply additional conditions.
 */
class ProductSearchQueryEvent extends Event
{
    const NAME = 'oro_product.search_restriction';

    /**
     * @var Query
     */
    private $query;

    /**
     * @param Query $query
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
