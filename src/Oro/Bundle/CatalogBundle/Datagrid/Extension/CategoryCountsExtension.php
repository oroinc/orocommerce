<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Extension;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\EventListener\SearchCategoryFilteringEventListener;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
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
    protected $datagridManager;

    /** @var RequestParameterBagFactory */
    protected $parametersFactory;

    /** @var CategoryRepository */
    protected $categoryRepository;

    /** @var ProductRepository */
    protected $productSearchRepository;

    /** @var array */
    protected $applicableGrids = [];

    /**
     * @param Manager $datagridManager
     * @param RequestParameterBagFactory $parametersFactory
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productSearchRepository
     */
    public function __construct(
        Manager $datagridManager,
        RequestParameterBagFactory $parametersFactory,
        CategoryRepository $categoryRepository,
        ProductRepository $productSearchRepository
    ) {
        $this->datagridManager = $datagridManager;
        $this->parametersFactory = $parametersFactory;
        $this->categoryRepository = $categoryRepository;
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
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        $categoryCounts = $this->getCounts($config);

        $filters = $data->offsetGetByPath('[filters]', []);
        foreach ($filters as &$filter) {
            if ($filter['type'] !== SubcategoryFilter::FILTER_TYPE_NAME) {
                continue;
            }

            $filter['counts'] = $categoryCounts;
        }
        unset($filter);

        $data->offsetSetByPath('[filters]', $filters);
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getCounts(DatagridConfiguration $config)
    {
        $category = $this->getCategory($config);
        if (!$category) {
            return [];
        }

        // build datagrid and extract search query from it
        $datagrid = $this->getDatagrid($config->getName());

        /** @var SearchDatasource $datasource */
        $datasource = $datagrid->acceptDatasource()->getDatasource();
        $searchQuery = $datasource->getSearchQuery();

        // calculate counts of products per category
        return $this->productSearchRepository->getCategoryCountsByCategory($category, $searchQuery);
    }

    /**
     * @param DatagridConfiguration $config
     * @return null|Category
     */
    protected function getCategory(DatagridConfiguration $config)
    {
        $categoryId = $config->offsetGetByPath(SearchCategoryFilteringEventListener::CATEGORY_ID_CONFIG_PATH);
        if (!$categoryId) {
            return null;
        }

        return $this->categoryRepository->find($categoryId);
    }

    /**
     * @param string $datagridName
     * @return DatagridInterface
     */
    protected function getDatagrid($datagridName)
    {
        // get datagrid parameters
        $datagridParameters = $this->parametersFactory->createParameters($datagridName);

        $categoryFilterName = SubcategoryFilter::FILTER_TYPE_NAME;

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

        return $this->datagridManager->getDatagrid($datagridName, $datagridParameters);
    }
}
