<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Datagrid\Filter\SubcategoryFilter;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Form\Type\Filter\SubcategoryFilterType;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Provider\SubcategoryProvider;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchCategoryFilteringEventListener
{
    const CATEGORY_ID_CONFIG_PATH = '[options][urlParams][categoryId]';
    const INCLUDE_CAT_CONFIG_PATH = '[options][urlParams][includeSubcategories]';
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
        $categoryId             = $event->getParameters()->get('categoryId');
        $isIncludeSubcategories = $event->getParameters()->get('includeSubcategories');
        if (!$categoryId) {
            $categoryId             = $this->requestProductHandler->getCategoryId();
            $isIncludeSubcategories = $this->requestProductHandler->getIncludeSubcategoriesChoice();
        }
        if (!$categoryId) {
            return;
        }

        $config = $event->getConfig();
        $config->offsetSetByPath(self::CATEGORY_ID_CONFIG_PATH, $categoryId);
        $config->offsetSetByPath(self::INCLUDE_CAT_CONFIG_PATH, $isIncludeSubcategories);
    }

    /**
     * @param DatagridConfiguration $config
     * @param int $categoryId
     */
    protected function addSubcategoryFilter(DatagridConfiguration $config, $categoryId)
    {
        $category = $this->repository->find($categoryId);

        $filters = $config->offsetGetByPath('[filters]', []);
        $filters['columns'][SubcategoryFilter::FILTER_TYPE_NAME] = [
            'data_name' => 'category_path',
            'label' => 'oro.catalog.filter.subcategory.label',
            'type' => SubcategoryFilter::FILTER_TYPE_NAME,
            'rootCategory' => $category,
            'options' => [
                'categories' => $this->categoryProvider->getAvailableSubcategories($category)
            ]
        ];

        $filters['default'][SubcategoryFilter::FILTER_TYPE_NAME] = [
            'type' => SubcategoryFilterType::TYPE_INCLUDE,
        ];

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

        $config = $event->getDatagrid()->getConfig();

        $categoryId           = $config->offsetGetByPath(self::CATEGORY_ID_CONFIG_PATH);
        $includeSubcategories = $config->offsetGetByPath(self::INCLUDE_CAT_CONFIG_PATH);

        if (!$categoryId) {
            return;
        }

        $config->offsetAddToArrayByPath(
            self::VIEW_LINK_PARAMS_CONFIG_PATH,
            [
                SluggableUrlGenerator::CONTEXT_TYPE => 'category',
                SluggableUrlGenerator::CONTEXT_DATA => $categoryId
            ]
        );

        $this->applyCategoryToQuery($datasource->getSearchQuery(), $categoryId, $includeSubcategories);
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

        if ($includeSubcategories) {
            $query->addWhere(Criteria::expr()->startsWith('text.category_path', $category->getMaterializedPath()));
        } else {
            $query->addWhere(Criteria::expr()->eq('text.category_path', $category->getMaterializedPath()));
        }
    }
}
