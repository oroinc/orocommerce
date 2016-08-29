<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class SearchCategoryFilteringEventListener
{
    /**
     * @var RequestProductHandler $requestProductHandler
     */
    private $requestProductHandler;

    /**
     * @param RequestProductHandler $requestProductHandler
     */
    public function __construct(RequestProductHandler $requestProductHandler)
    {
        $this->requestProductHandler = $requestProductHandler;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();

        if ($datasource instanceof SearchDatasource) {
            $categoryId = $this->getCurrentCategoryId();

            if ($categoryId > 0) {
                $this->applyCategoryToQuery($datasource->getQuery(), $categoryId);
            }
        }
    }

    /**
     * @return int
     */
    private function getCurrentCategoryId()
    {
        return (int)$this->requestProductHandler->getCategoryId();
    }

    /**
     * @param WebsiteSearchQuery $query
     * @param $categoryId
     */
    private function applyCategoryToQuery(WebsiteSearchQuery $query, $categoryId)
    {
        $query->getQuery()->andWhere('integer.cat_id', '=', $categoryId, 'integer');
    }
}
