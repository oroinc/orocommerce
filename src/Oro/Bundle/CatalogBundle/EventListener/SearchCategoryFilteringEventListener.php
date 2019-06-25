<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

/**
 * Search event listener for category datagrid
 */
class SearchCategoryFilteringEventListener
{
    const CATEGORY_ID_CONFIG_PATH = '[options][urlParams][categoryId]';
    const INCLUDE_CAT_CONFIG_PATH = '[options][urlParams][includeSubcategories]';
    const OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH = '[options][urlParams][overrideVariantConfiguration]';
    const VIEW_LINK_PARAMS_CONFIG_PATH = '[properties][view_link][direct_params]';

    /** @var RequestProductHandler $requestProductHandler */
    private $requestProductHandler;

    /** @var CategoryRepository */
    private $repository;

    /** @var SubcategoryProvider */
    private $categoryProvider;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param CategoryRepository $categoryRepository
     * @param SubcategoryProvider $categoryProvider
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        CategoryRepository $categoryRepository,
        SubcategoryProvider $categoryProvider
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->repository = $categoryRepository;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $categoryId = $this->requestProductHandler->getCategoryId();
        $isIncludeSubcategories = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        $overrideVariantConfiguration = $this->requestProductHandler->getOverrideVariantConfiguration();

        $parameters = $event->getParameters();

        if (!$categoryId) {
            $categoryId = $this->getCategoryId($parameters);
            $isIncludeSubcategories = $this->getIncludeSubcategories($parameters);
            $overrideVariantConfiguration = $this->getOverrideVariantConfiguration($parameters);
        } else {
            $parameters->set('categoryId', $categoryId);
            $parameters->set('includeSubcategories', $isIncludeSubcategories);
            $parameters->set(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY, $overrideVariantConfiguration);
        }

        if (!$categoryId) {
            return;
        }

        $config = $event->getConfig();
        $config->offsetSetByPath(self::CATEGORY_ID_CONFIG_PATH, $categoryId);
        $config->offsetSetByPath(self::INCLUDE_CAT_CONFIG_PATH, $isIncludeSubcategories);
        $config->offsetSetByPath(
            self::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH,
            (int) $overrideVariantConfiguration
        );

        $this->addSubcategoryFilter($config, $categoryId, $isIncludeSubcategories);
    }

    /**
     * @param DatagridConfiguration $config
     * @param int $categoryId
     * @param bool $includeSubcategories
     */
    protected function addSubcategoryFilter(DatagridConfiguration $config, $categoryId, $includeSubcategories)
    {
        /** @var Category $category */
        $category = $this->repository->find($categoryId);
        $subcategories = $this->categoryProvider->getAvailableSubcategories($category);

        $filters = $config->offsetGetByPath(Configuration::FILTERS_PATH, []);
        $filters['columns'][SubcategoryFilter::FILTER_TYPE_NAME] = [
            'data_name' => 'category_path',
            'label' => 'oro.catalog.filter.subcategory.label',
            'type' => SubcategoryFilter::FILTER_TYPE_NAME,
            'rootCategory' => $category,
            'options' => [
                'choices' => $subcategories
            ]
        ];

        if ($includeSubcategories) {
            $filters['default'][SubcategoryFilter::FILTER_TYPE_NAME] = [
                'value' => SubcategoryFilter::DEFAULT_VALUE,
            ];
        }

        $config->offsetSetByPath('[filters]', $filters);
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();
        if (!$datasource instanceof SearchDatasource) {
            return;
        }

        $grid = $event->getDatagrid();
        $parameters = $grid->getParameters();

        $categoryId = $this->getCategoryId($parameters);
        if (!$categoryId) {
            return;
        }

        $config = $grid->getConfig();
        $config->offsetAddToArrayByPath(
            self::VIEW_LINK_PARAMS_CONFIG_PATH,
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                SluggableUrlGenerator::CONTEXT_DATA => $categoryId
            ]
        );

        $this->applyCategoryToQuery(
            $datasource->getSearchQuery(),
            $categoryId,
            $this->getIncludeSubcategories($parameters)
        );

        if ($parameters->get(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY)) {
            $datasource->getSearchQuery()->addWhere(Criteria::expr()->gte('integer.is_variant', 0));
        }
    }

    /**
     * @param SearchQueryInterface $query
     * @param int $categoryId
     * @param bool $includeSubcategories
     */
    private function applyCategoryToQuery(SearchQueryInterface $query, $categoryId, $includeSubcategories = false)
    {
        /** @var Category $category */
        $category = $this->repository->find($categoryId);

        if (!$includeSubcategories) {
            $query->addWhere(Criteria::expr()->eq('text.category_path', $category->getMaterializedPath()));
        }
    }

    /**
     * @param ParameterBag $parameters
     * @return int
     */
    private function getCategoryId(ParameterBag $parameters)
    {
        $categoryId = filter_var($parameters->get('categoryId', 0), FILTER_VALIDATE_INT);

        return $categoryId && $categoryId > 0 ? $categoryId : 0;
    }

    /**
     * @param ParameterBag $parameters
     * @return bool
     */
    private function getIncludeSubcategories(ParameterBag $parameters)
    {
        return filter_var($parameters->get('includeSubcategories', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param ParameterBag $parameters
     * @return bool
     */
    private function getOverrideVariantConfiguration(ParameterBag $parameters)
    {
        return filter_var(
            $parameters->get(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }
}
