<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * Contains data for event that is raised during building a search query that is used to load a product list data.
 */
class BuildQueryProductListEvent extends ProductListEvent
{
    public const NAME = 'oro_product.product_list.build_query';

    private SearchQueryInterface $query;

    public function __construct(string $productListType, SearchQueryInterface $query)
    {
        parent::__construct($productListType);
        $this->query = $query;
    }

    public function getQuery(): SearchQueryInterface
    {
        return $this->query;
    }
}
