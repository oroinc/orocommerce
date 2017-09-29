<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datagrid\RequestParameterBagFactory;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;

class CategoryCountsExtension extends AbstractExtension
{
    /** @var Manager */
    private $datagridManager;

    /** @var RequestParameterBagFactory */
    private $parametersFactory;

    /** @var ProductRepository */
    private $productSearchRepository;

    /** @var array */
    private $applicableGrids = [];

    public function __construct(
        Manager $datagridManager,
        RequestParameterBagFactory $parametersFactory,
        ProductRepository $productSearchRepository
    ) {
        $this->datagridManager = $datagridManager;
        $this->parametersFactory = $parametersFactory;
        $this->productSearchRepository = $productSearchRepository;
    }

    /**
     * @param string $gridName
     */
    public function addApplicableGrid($gridName)
    {
        $this->applicableGrids[] = $gridName;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            SearchDatasource::TYPE === $config->getDatasourceType() &&
            in_array($config->getName(), $this->applicableGrids, true);
    }

    /**
     * {@inheritDoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $categoryFilterName = 'category_filter';

        // get datagrid parameters
        $datagridName = $config->getName();
        $datagridParameters = $this->parametersFactory->createParameters($datagridName);

        // remove filter by category to make sure that filter counts will not be affected by filter itself
        $filters = $datagridParameters->get(AbstractFilterExtension::FILTER_ROOT_PARAM);
        if ($filters) {
            unset($filters[$categoryFilterName]);
            $datagridParameters->set(AbstractFilterExtension::FILTER_ROOT_PARAM, $filters);
        }
        $minifiedFilters = $datagridParameters->get(ParameterBag::MINIFIED_PARAMETERS);
        if ($minifiedFilters) {
            unset($minifiedFilters[AbstractFilterExtension::MINIFIED_FILTER_PARAM][$categoryFilterName]);
            $datagridParameters->set(ParameterBag::MINIFIED_PARAMETERS, $minifiedFilters);
        }

        // build datagrid and extract search query from it
        $datagrid = $this->datagridManager->getDatagrid($datagridName, $datagridParameters);
        /** @var SearchDatasource $datasource */
        $datasource = $datagrid->acceptDatasource()->getDatasource();
        $searchQuery = $datasource->getSearchQuery();

        // calculate counts of products per category
        $categoryCounts = $this->productSearchRepository->getCategoryCounts($searchQuery);

        // add data to result
        $filterCounts = $result->offsetGetByPath('filterCounts', []);
        $filterCounts[$categoryFilterName] = $categoryCounts;
        $result->offsetSetByPath('filterCounts', $filterCounts);
    }
}
