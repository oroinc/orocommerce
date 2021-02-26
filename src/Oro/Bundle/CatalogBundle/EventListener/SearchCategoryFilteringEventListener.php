<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
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
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

/**
 * Datagrid preBuild/afterBuild event listener which adds a subcategory filter, extra parameters and extra criterias
 * to a datagrid and its search datasource query for displaying a category page and category page content variant.
 */
class SearchCategoryFilteringEventListener
{
    private const CONTENT_VARIANT_ID_CONFIG_PATH = '[options][urlParams][contentVariantId]';
    private const CATEGORY_ID_CONFIG_PATH = '[options][urlParams][categoryId]';
    private const INCLUDE_CAT_CONFIG_PATH = '[options][urlParams][includeSubcategories]';
    private const OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH = '[options][urlParams][overrideVariantConfiguration]';
    private const VIEW_LINK_PARAMS_CONFIG_PATH = '[properties][view_link][direct_params]';

    /** @var RequestProductHandler $requestProductHandler */
    private $requestProductHandler;

    /** @var ManagerRegistry */
    private $registry;

    /** @var SubcategoryProvider */
    private $categoryProvider;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param ManagerRegistry $registry
     * @param SubcategoryProvider $categoryProvider
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        ManagerRegistry $registry,
        SubcategoryProvider $categoryProvider
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->registry = $registry;
        $this->categoryProvider = $categoryProvider;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $parameters = $event->getParameters();

        $categoryId = $this->getCategoryId($parameters);
        if (!$categoryId) {
            return;
        }

        $config = $event->getConfig();

        $parameters->set(RequestProductHandler::CATEGORY_ID_KEY, $categoryId);
        $config->offsetSetByPath(self::CATEGORY_ID_CONFIG_PATH, $categoryId);

        $isIncludeSubcategories = $this->isIncludeSubcategories($parameters);
        $parameters->set(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY, $isIncludeSubcategories);
        $config->offsetSetByPath(self::INCLUDE_CAT_CONFIG_PATH, $isIncludeSubcategories);

        $this->addSubcategoryFilter($config, $categoryId, $isIncludeSubcategories);

        $contentVariantId = $this->getContentVariantId($parameters);
        $contentVariant = $this->getCategoryContentVariant($contentVariantId);
        if (!$contentVariant) {
            // Skips adding contentVariantId to config and parameters if content variant is not found or
            // not of category page type.
            return;
        }

        $parameters->set(RequestProductHandler::CONTENT_VARIANT_ID_KEY, $contentVariantId);
        $config->offsetSetByPath(self::CONTENT_VARIANT_ID_CONFIG_PATH, $contentVariantId);

        $overrideVariantConfiguration = $this->isOverrideVariantConfiguration($parameters);
        $parameters->set(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY, $overrideVariantConfiguration);
        $config->offsetSetByPath(self::OVERRIDE_VARIANT_CONFIGURATION_CONFIG_PATH, (int)$overrideVariantConfiguration);
    }

    private function getCategoryContentVariant(int $contentVariantId): ?ContentVariantInterface
    {
        if ($contentVariantId) {
            // Method find() is used here because it does not make a query to database if entity is already present
            // in unitOfWork.
            $contentVariant = $this->registry
                ->getManagerForClass(ContentVariant::class)
                ->find(ContentVariant::class, $contentVariantId);
        }

        return !empty($contentVariant) && $contentVariant->getType() === ProductCollectionContentVariantType::TYPE
            ? $contentVariant
            : null;
    }

    /**
     * @param DatagridConfiguration $config
     * @param int $categoryId
     * @param bool $includeSubcategories
     */
    protected function addSubcategoryFilter(DatagridConfiguration $config, $categoryId, $includeSubcategories)
    {
        /** @var Category $category */
        $category = $this->getCategoryRepository()->find($categoryId);
        if (!$category) {
            return;
        }

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
            $this->isIncludeSubcategories($parameters)
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
        $category = $this->getCategoryRepository()->find($categoryId);

        if (!$includeSubcategories) {
            $query->addWhere(Criteria::expr()->eq('text.category_path', $category->getMaterializedPath()));
        }
    }

    private function getCategoryId(ParameterBag $parameters): int
    {
        $categoryId = filter_var(
            $parameters->get(RequestProductHandler::CATEGORY_ID_KEY),
            FILTER_VALIDATE_INT
        );

        return $categoryId && $categoryId > 0 ? $categoryId : $this->requestProductHandler->getCategoryId();
    }

    private function getContentVariantId(ParameterBag $parameters): int
    {
        $contentVariantId = filter_var(
            $parameters->get(ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY),
            FILTER_VALIDATE_INT
        );

        return $contentVariantId && $contentVariantId > 0
            ? $contentVariantId
            : $this->requestProductHandler->getContentVariantId();
    }

    private function isIncludeSubcategories(ParameterBag $parameters): bool
    {
        $includeSubcategories = $parameters->has(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY)
            ? $parameters->get(RequestProductHandler::INCLUDE_SUBCATEGORIES_KEY)
            : $this->requestProductHandler->getIncludeSubcategoriesChoice();

        return filter_var($includeSubcategories, FILTER_VALIDATE_BOOLEAN);
    }

    private function isOverrideVariantConfiguration(ParameterBag $parameters): ?bool
    {
        $overrideVariantConfiguration = $parameters->has(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY)
            ? $parameters->get(RequestProductHandler::OVERRIDE_VARIANT_CONFIGURATION_KEY)
            : $this->requestProductHandler->getOverrideVariantConfiguration();

        return filter_var($overrideVariantConfiguration, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @return CategoryRepository
     */
    private function getCategoryRepository(): CategoryRepository
    {
        return $this->registry->getManagerForClass(Category::class)
            ->getRepository(Category::class);
    }
}
