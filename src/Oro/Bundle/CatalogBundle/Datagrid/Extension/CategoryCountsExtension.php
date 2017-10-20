<?php

namespace Oro\Bundle\CatalogBundle\Datagrid\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Search\ProductRepository;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Component\DependencyInjection\ServiceLink;

class CategoryCountsExtension extends AbstractExtension
{
    const SKIP_PARAM = 'skipCategoryCountsExtension';

    /** @var ServiceLink */
    protected $datagridManagerLink;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ProductRepository */
    protected $productSearchRepository;

    /** @var array */
    protected $applicableGrids = [];

    /**
     * @param ServiceLink $datagridManagerLink
     * @param ManagerRegistry $registry
     * @param ProductRepository $productSearchRepository
     */
    public function __construct(
        ServiceLink $datagridManagerLink,
        ManagerRegistry $registry,
        ProductRepository $productSearchRepository
    ) {
        $this->datagridManagerLink = $datagridManagerLink;
        $this->registry = $registry;
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
            !$this->parameters->get(self::SKIP_PARAM) &&
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
        if (!filter_var($this->parameters->get('includeSubcategories'), FILTER_VALIDATE_BOOLEAN)) {
            return [];
        }

        $category = $this->getCategory();
        if (!$category) {
            return [];
        }

        // build datagrid and extract search query from it
        $datagrid = $this->getGrid($config);

        /** @var SearchDatasource $datasource */
        $datasource = $datagrid->acceptDatasource()->getDatasource();

        // calculate counts of products per category
        return $this->productSearchRepository->getCategoryCountsByCategory($category, $datasource->getSearchQuery());
    }

    /**
     * @return null|Category
     */
    protected function getCategory()
    {
        $categoryId = filter_var($this->parameters->get('categoryId'), FILTER_VALIDATE_INT);

        return $categoryId && $categoryId > 0 ? $this->getCategoryRepository()->find($categoryId) : null;
    }

    /**
     * @param DatagridConfiguration $config
     *
     * @return DatagridInterface
     */
    protected function getGrid(DatagridConfiguration $config)
    {
        // get datagrid parameters
        $datagridParameters = clone $this->parameters;
        $datagridParameters->set(self::SKIP_PARAM, true);

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

        /** @var Manager $datagridManager */
        $datagridManager = $this->datagridManagerLink->getService();

        return $datagridManager->getDatagrid($config->getName(), $datagridParameters);
    }

    /**
     * @return CategoryRepository
     */
    protected function getCategoryRepository()
    {
        return $this->registry
            ->getManagerForClass(Category::class)
            ->getRepository(Category::class);
    }
}
