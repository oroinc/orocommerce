<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Tools\DatagridParametersHelper;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Extension adds counts for each option to filter metadata for subcategory filter
 */
class CategoryCountsExtension extends AbstractExtension
{
    /** @var ServiceLink */
    private $datagridManagerLink;

    /** @var ManagerRegistry */
    private $registry;

    /** @var ProductRepository */
    private $productSearchRepository;

    /** @var CategoryCountsCache */
    private $cache;

    /** @var DatagridParametersHelper */
    private $datagridParametersHelper;

    /** @var array */
    private $applicableGrids = [];

    /** @var ConfigManager */
    private $configManager;

    /**
     * @var bool[] Stores flags about already applied datagrids.
     * [
     *      '<datagridName>' => <bool>,
     *      ...
     * ]
     */
    private $applied = [];

    public function __construct(
        ServiceLink $datagridManagerLink,
        ManagerRegistry $registry,
        ProductRepository $productSearchRepository,
        CategoryCountsCache $cache,
        DatagridParametersHelper $datagridParametersHelper,
        ConfigManager $configManager
    ) {
        $this->datagridManagerLink = $datagridManagerLink;
        $this->registry = $registry;
        $this->productSearchRepository = $productSearchRepository;
        $this->cache = $cache;
        $this->datagridParametersHelper = $datagridParametersHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param string $gridName
     */
    public function addApplicableGrid($gridName): void
    {
        $this->applicableGrids[] = $gridName;
    }

    /**
     * {@inheritDoc}
     */
    public function getPriority()
    {
        return -250;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && !$this->datagridParametersHelper->isDatagridExtensionSkipped($this->getParameters())
            && SearchDatasource::TYPE === $config->getDatasourceType()
            && in_array($config->getName(), $this->applicableGrids, true);
    }

    /**
     * {@inheritDoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data)
    {
        // Skips handling of metadata if datagrid has been already processed.
        if (!empty($this->applied[$config->getName()])) {
            return;
        }

        $this->applied[$config->getName()] = true;

        $categoryCounts = $this->getCounts($config);

        $countsWithoutFilters = [];
        $isDisableFiltersEnabled = $this->isOptionsDisablingApplicable();
        if ($isDisableFiltersEnabled) {
            $countsWithoutFilters = $this->getCountsWithoutFilters($config);
        }

        $filters = $data->offsetGetByPath('[filters]', []);
        foreach ($filters as &$filter) {
            if ($filter['type'] !== SubcategoryFilter::FILTER_TYPE_NAME) {
                continue;
            }

            $filter['counts'] = $categoryCounts;
            $filter['countsWithoutFilters'] = $countsWithoutFilters;
            $filter['isDisableFiltersEnabled'] = $isDisableFiltersEnabled;
        }
        unset($filter);

        $data->offsetSetByPath('[filters]', $filters);
    }

    protected function getCounts(DatagridConfiguration $config): array
    {
        return $this->getFilterCounts($config);
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool $resetFilters
     * @return array
     */
    protected function getCountsWithoutFilters(DatagridConfiguration $config, $resetFilters = true): array
    {
        return $this->getFilterCounts($config, $resetFilters);
    }

    /**
     * @param DatagridConfiguration $config
     * @param bool $resetFilters
     * @return array
     */
    private function getFilterCounts(DatagridConfiguration $config, $resetFilters = false): array
    {
        if (!filter_var($this->parameters->get('includeSubcategories'), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $category = $this->getCategory();
        if (!$category) {
            return [];
        }

        // remove filter by category to make sure that filter counts will not be affected by filter itself
        $parameters = clone $this->getParameters();

        if ($resetFilters) {
            $this->datagridParametersHelper->resetFilters($parameters);
        } else {
            $this->datagridParametersHelper->resetFilter($parameters, SubcategoryFilter::FILTER_TYPE_NAME);
        }

        // merge common parameters filters with minified parameters filters for
        // create correct cache key after reload the page
        $filtersParameters = array_merge(
            (array) $this->datagridParametersHelper->getFromParameters(
                $parameters,
                AbstractFilterExtension::FILTER_ROOT_PARAM
            ),
            (array) $this->datagridParametersHelper->getFromMinifiedParameters(
                $parameters,
                AbstractFilterExtension::MINIFIED_FILTER_PARAM
            )
        );
        $parameters->set(AbstractFilterExtension::FILTER_ROOT_PARAM, $filtersParameters);

        $this->datagridParametersHelper->setDatagridExtensionSkipped($parameters);

        $cacheKey = $this->getCacheKey($config->getName(), $parameters);

        $counts = $this->cache->getCounts($cacheKey);
        if ($counts === null) {
            // build datagrid and extract search query from it
            $datagrid = $this->getGrid($config, $parameters);

            /** @var SearchDatasource $datasource */
            $datasource = $datagrid->acceptDatasource()->getDatasource();

            // calculate counts of products per category
            $counts = $this->productSearchRepository->getCategoryCountsByCategory(
                $category,
                $datasource->getSearchQuery()
            );

            // store cache for 5 minutes to prevent overload of search index
            $this->cache->setCounts($cacheKey, $counts, 300);
        }

        return $counts;
    }

    /**
     * @return null|Category
     */
    protected function getCategory()
    {
        $categoryId = filter_var($this->parameters->get('categoryId'), FILTER_VALIDATE_INT);

        return $categoryId && $categoryId > 0 ? $this->getCategoryRepository()->find($categoryId) : null;
    }

    protected function getGrid(DatagridConfiguration $config, ParameterBag $datagridParameters): DatagridInterface
    {
        /** @var Manager $datagridManager */
        $datagridManager = $this->datagridManagerLink->getService();

        return $datagridManager->getDatagrid($config->getName(), $datagridParameters);
    }

    protected function getCategoryRepository(): CategoryRepository
    {
        return $this->registry
            ->getManagerForClass(Category::class)
            ->getRepository(Category::class);
    }

    /**
     * @param string $gridName
     * @param array $parameters
     * @return string
     */
    private function getDataKey($gridName, array $parameters): string
    {
        $this->sort($parameters);

        return sprintf('%s|%s', $gridName, json_encode($parameters, JSON_NUMERIC_CHECK));
    }

    /**
     * @param mixed $parameters
     */
    private function sort(&$parameters)
    {
        if (is_array($parameters)) {
            ksort($parameters);
            array_walk($parameters, [$this, 'sort']);
        }
    }

    /**
     * Get cache key by applicable parameters only to avoid redundant request
     *
     * @param string       $gridName
     * @param ParameterBag $datagridParameters
     *
     * @return string
     */
    private function getCacheKey($gridName, ParameterBag $datagridParameters): string
    {
        $parameters = clone $datagridParameters;
        $applicableParameters = $this->getApplicableParameters();
        foreach ($parameters->all() as $name => $value) {
            if (!in_array($name, $applicableParameters, true)) {
                $parameters->remove($name);
            }
        }

        return $this->getDataKey($gridName, array_filter($parameters->all()));
    }

    /**
     * Get array of parameters that should be taken into account for generating cache key
     */
    private function getApplicableParameters(): array
    {
        return [
            'categoryId',
            DatagridParametersHelper::DATAGRID_SKIP_EXTENSION_PARAM,
            AbstractFilterExtension::FILTER_ROOT_PARAM
        ];
    }

    /**
     * @return bool
     */
    private function isOptionsDisablingApplicable()
    {
        $limitFilters = Configuration::getConfigKeyByName(Configuration::LIMIT_FILTERS_SORTERS_ON_PRODUCT_LISTING);
        $disableFilters = Configuration::getConfigKeyByName(Configuration::DISABLE_FILTERS_ON_PRODUCT_LISTING);

        return $this->configManager->get($limitFilters) && $this->configManager->get($disableFilters);
    }
}
