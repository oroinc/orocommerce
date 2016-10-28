<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Category;
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

    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param RequestProductHandler $requestProductHandler
     * @param ManagerRegistry       $doctrine
     */
    public function __construct(
        RequestProductHandler $requestProductHandler,
        ManagerRegistry $doctrine
    ) {
        $this->requestProductHandler = $requestProductHandler;
        $this->doctrine              = $doctrine;
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

        $categoryIds = $this->getSubcategories($categoryId);
        $this->applyCategoryToQuery($datasource->getSearchQuery(), $categoryIds);
    }

    /**
     * @param $categoryId
     * @return array
     */
    private function getSubcategories($categoryId)
    {
        /** @var CategoryRepository $repo */
        $repo = $this->doctrine->getRepository(Category::class);
        /** @var Category $category */
        $category = $repo->find($categoryId);

        if (!$category) {
            return [];
        }

        $result   = $repo->getChildrenIds($category);
        $result[] = $categoryId;

        return $result;
    }

    /**
     * @param SearchQueryInterface $query
     * @param array|int            $categoryId
     */
    private function applyCategoryToQuery(SearchQueryInterface $query, $categoryId)
    {
        if (is_array($categoryId)) {
            $expr = Criteria::expr()->in('integer.category_id', $categoryId);
        } else {
            $expr = Criteria::expr()->eq('integer.category_id', $categoryId);
        }

        $query->addWhere($expr);
    }
}
