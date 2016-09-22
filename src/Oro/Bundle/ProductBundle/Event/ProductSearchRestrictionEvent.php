<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * This event is fired by the Product Manager when
 * search query restriction should be applied.
 * The listeners of this event should modify the inner
 * query to apply additional conditions.
 */
class ProductSearchRestrictionEvent extends Event
{
    const NAME = 'oro_product.search_restriction';

    /**
     * @var SearchQuery
     */
    private $query;

    /**
     * @param SearchQuery $query
     */
    public function __construct(SearchQuery $query)
    {
        $this->query = $query;
    }

    /**
     * @return SearchQuery
     */
    public function getQuery()
    {
        return $this->query;
    }
}
