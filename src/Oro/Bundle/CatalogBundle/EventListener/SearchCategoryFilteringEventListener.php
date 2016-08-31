<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Extension\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Query\Query;

class SearchCategoryFilteringEventListener
{
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
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datasource = $event->getDatagrid()->getDatasource();

        if ($datasource instanceof SearchDatasource) {
            $categoryId           = $this->requestProductHandler->getCategoryId();
            $includeSubcategories = $this->requestProductHandler->getIncludeSubcategoriesChoice();

            if (!$categoryId) {
                return;
            }

            if (!$includeSubcategories) {
                $this->applyCategoryToQuery($datasource->getQuery(), $categoryId);
                return;
            }

            $categoryIds = $this->getSubcategories($categoryId);
            $this->applyCategoryToQuery($datasource->getQuery(), $categoryIds);
        }
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
     * @param                      $categoryId
     */
    private function applyCategoryToQuery(SearchQueryInterface $query, $categoryId)
    {
        $query->getQuery()->andWhere(
            'cat_id',
            is_array($categoryId) ? Query::OPERATOR_IN : Query::OPERATOR_EQUALS,
            $categoryId,
            'integer'
        );
    }
}
