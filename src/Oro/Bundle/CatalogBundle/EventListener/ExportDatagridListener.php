<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
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

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function onBeforeExportGetIds(ExportPreGetIds $event)
    {
        $options = $event->getOptions();
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

        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->in(sprintf('%s.category', $alias), $categoryIds));

        return $categoryExpression;
    }

    /**
     * @param null|Expr\Base $categoryExpression
     * @param QueryBuilder $qb
     * @return null|Expr\Base
     */
    private function applyIncludeNotCategorizedProducts($categoryExpression, QueryBuilder $qb)
    {
        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->isNull(sprintf('%s.category', $alias)));

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
