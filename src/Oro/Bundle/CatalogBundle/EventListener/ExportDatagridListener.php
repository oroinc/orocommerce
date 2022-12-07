<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Datagrid\CategoryFilterInterface;
use Oro\Bundle\CatalogBundle\ImportExport\Datagrid\CategoryFilterRegistryInterface;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * This listener adds category filtering to query builder on product export event
 */
class ExportDatagridListener
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var CategoryRepository */
    private $repository = null;

    /** @var CategoryFilterRegistryInterface */
    private $categoryFilterRegistry;

    /** @var CategoryFilterInterface|null  */
    private $categoryFilter = null;


    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function setCategoryFilterRegistry(CategoryFilterRegistryInterface $categoryFilterRegistry)
    {
        $this->categoryFilterRegistry = $categoryFilterRegistry;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function onBeforeExportGetIds(ExportPreGetIds $event)
    {
        $options = $event->getOptions();
        $this->categoryFilter = $this->categoryFilterRegistry->get($options['entityName'] ?? '');
        $categoryExpression = null;

        if (isset($options['categoryId'])) {
            $categoryExpression = $this->applyCategory(
                $categoryExpression,
                $event->getQueryBuilder(),
                (int)$options['categoryId'],
                (bool)($options['includeSubcategories'] ?? false)
            );
        }

        if ($options['includeNotCategorizedProducts'] ?? false) {
            $categoryExpression = $this->applyIncludeNotCategorizedProducts(
                $categoryExpression,
                $event->getQueryBuilder()
            );
        }

        if (null === $categoryExpression) {
            return;
        }

        $this->categoryFilter->prepareQueryBuilder($event->getQueryBuilder());
        $event->getQueryBuilder()->andWhere($categoryExpression);
    }

    /**
     * @param null|Expr\Base $categoryExpression
     * @param QueryBuilder $qb
     * @param int $categoryId
     * @param bool $isIncludeSubcategories
     * @return null|Expr\Base
     */
    private function applyCategory(
        $categoryExpression,
        QueryBuilder $qb,
        int $categoryId,
        bool $isIncludeSubcategories = false
    ) {
        /** @var Category $category */
        $category = $this->getRepository()->find($categoryId);
        if (!$category) {
            return $categoryExpression;
        }

        $categoryIds = [$categoryId];
        if ($isIncludeSubcategories) {
            $categoryIds = array_merge($this->getRepository()->getChildrenIds($category), $categoryIds);
        }

        $fieldName = $this->categoryFilter->getFieldName($qb);
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->in($fieldName, $categoryIds));

        return $categoryExpression;
    }

    /**
     * @param null|Expr\Base $categoryExpression
     * @param QueryBuilder $qb
     * @return null|Expr\Base
     */
    private function applyIncludeNotCategorizedProducts($categoryExpression, QueryBuilder $qb)
    {
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->isNull($this->categoryFilter->getFieldName($qb)));

        return $categoryExpression;
    }

    private function getRepository(): CategoryRepository
    {
        if (null === $this->repository) {
            $this->repository = $this->registry->getRepository(Category::class);
        }

        return $this->repository;
    }
}
