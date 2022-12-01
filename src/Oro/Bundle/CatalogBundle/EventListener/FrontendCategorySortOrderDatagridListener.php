<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Adds default sorting when product grid has products from the master catalog category
 */
class FrontendCategorySortOrderDatagridListener
{
    public function onSearchResultBefore(SearchResultBefore $event)
    {
        // Adds category sort order info to the product datagrid
        if ($event->getDatagrid()->getParameters()->get(RequestProductHandler::CATEGORY_ID_KEY)) {
            if (!$event->getQuery()->getSortOrder()) {
                $event->getQuery()
                    ->setOrderBy(
                        'decimal.category_sort_order',
                        Query::ORDER_ASC,
                        Query::TYPE_DECIMAL
                    );
            }
        }
    }
}
