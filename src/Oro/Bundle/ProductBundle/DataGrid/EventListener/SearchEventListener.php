<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\Handler\SearchProductHandler;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;

/**
 * Managed for product search and provides the opportunity to work with a subset of products in the product grid
 */
class SearchEventListener
{
    /**
     * @var SearchProductHandler
     */
    private $searchProductHandler;

    /**
     * @param SearchProductHandler $searchProductHandler
     */
    public function __construct(SearchProductHandler $searchProductHandler)
    {
        $this->searchProductHandler = $searchProductHandler;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event): void
    {
        $searchString = $event->getParameters()->has(SearchProductHandler::SEARCH_KEY)
            ? $event->getParameters()->get(SearchProductHandler::SEARCH_KEY)
            : $this->searchProductHandler->getSearchString();


        if ($searchString) {
            $event->getConfig()->offsetSetByPath($this->getConfigPath(), $searchString);

            return;
        }
        $event->getConfig()->offsetUnsetByPath($this->getConfigPath());
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event): void
    {
        $dataSource = $event->getDatagrid()->getDatasource();
        $searchString = $event->getDatagrid()->getConfig()->offsetGetByPath($this->getConfigPath());

        if (!$dataSource instanceof SearchDatasource || !$searchString) {
            return;
        }

        $query = $dataSource->getSearchQuery();
        $query->addWhere(Criteria::expr()->contains('all_text_LOCALIZATION_ID', $searchString));
    }

    /**
     * @return string
     */
    private function getConfigPath(): string
    {
        return sprintf('[options][urlParams][%s]', SearchProductHandler::SEARCH_KEY);
    }
}
