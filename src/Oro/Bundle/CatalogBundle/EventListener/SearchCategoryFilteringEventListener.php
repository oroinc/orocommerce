<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;

class SearchCategoryFilteringEventListener
{
    const CATEGORY_ID_CONFIG_PATH = '[options][urlParams][categoryId]';
    const INCLUDE_CAT_CONFIG_PATH = '[options][urlParams][includeSubcategories]';

    /** @var RequestProductHandler $requestProductHandler */
    private $requestProductHandler;

    /** @var CategoryRepository */
    private $repository;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        CategoryRepository $categoryRepository
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->repository = $categoryRepository;
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

        if (!$includeSubcategories) {
            $this->applyCategoryToQuery($datasource->getSearchQuery(), $categoryId);
            return;
        }

        $this->applyCategoryToQuery($datasource->getSearchQuery(), $categoryId, $includeSubcategories);
    }

    /**
     * @param SearchQueryInterface $query
     * @param int $categoryId
     * @param bool $includeSubcategories
     */
    private function applyCategoryToQuery(SearchQueryInterface $query, $categoryId, $includeSubcategories = false)
    {
        $category = $this->repository->find($categoryId);

        if ($includeSubcategories) {
            $query->addWhere(Criteria::expr()->startsWith('text.cat_path', $category->getMaterializedPath()));
        } else {
            $query->addWhere(Criteria::expr()->eq('text.cat_path', $category->getMaterializedPath()));
        }
    }
}
