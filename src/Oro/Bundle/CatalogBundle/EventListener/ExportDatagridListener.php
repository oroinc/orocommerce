<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * This listener adds category filtering to query builder on product export event
 */
class ExportDatagridListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function onBeforeExportGetIds(ExportPreGetIds $event): void
    {
        $options = $event->getOptions();
        $categoryExpression = null;

        if (isset($options['categoryId'])) {
            $categoryExpression = $this->applyCategory(
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

    private function applyCategory(QueryBuilder $qb, int $categoryId, bool $isIncludeSubcategories): ?Expr\Base
    {
        /** @var CategoryRepository $repository */
        $repository = $this->doctrine->getRepository(Category::class);
        /** @var Category $category */
        $category = $repository->find($categoryId);
        if (!$category) {
            return null;
        }

        $categoryIds = [$categoryId];
        if ($isIncludeSubcategories) {
            $categoryIds = array_merge($repository->getChildrenIds($category), $categoryIds);
        }

        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        $categoryExpression = $qb->expr()->orX();
        $categoryExpression->add($qb->expr()->in(sprintf('%s.category', $alias), $categoryIds));

        return $categoryExpression;
    }

    private function applyIncludeNotCategorizedProducts(?Expr\Base $categoryExpression, QueryBuilder $qb): ?Expr\Base
    {
        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->isNull(sprintf('%s.category', $alias)));

        return $categoryExpression;
    }
}
