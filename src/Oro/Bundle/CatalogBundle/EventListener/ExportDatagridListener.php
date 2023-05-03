<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Datagrid\CategoryFilterInterface;
use Oro\Bundle\CatalogBundle\ImportExport\Datagrid\CategoryFilterRegistryInterface;
use Oro\Bundle\ImportExportBundle\Event\ExportPreGetIds;

/**
 * This listener adds category filtering to query builder on product export event
 */
class ExportDatagridListener
{
    private ManagerRegistry $doctrine;
    private CategoryFilterRegistryInterface $categoryFilterRegistry;
    private ?CategoryFilterInterface $categoryFilter = null;

    public function __construct(ManagerRegistry $doctrine, CategoryFilterRegistryInterface $categoryFilterRegistry)
    {
        $this->doctrine = $doctrine;
        $this->categoryFilterRegistry = $categoryFilterRegistry;
    }

    public function onBeforeExportGetIds(ExportPreGetIds $event): void
    {
        $options = $event->getOptions();
        $this->categoryFilter = $this->categoryFilterRegistry->get($options['entityName'] ?? '');
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

        $this->categoryFilter->prepareQueryBuilder($event->getQueryBuilder());
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

        $categoryExpression = $qb->expr()->orX();
        $categoryExpression->add($qb->expr()->in($this->categoryFilter->getFieldName($qb), $categoryIds));

        return $categoryExpression;
    }

    private function applyIncludeNotCategorizedProducts(?Expr\Base $categoryExpression, QueryBuilder $qb): ?Expr\Base
    {
        if (null === $categoryExpression) {
            $categoryExpression = $qb->expr()->orX();
        }
        $categoryExpression->add($qb->expr()->isNull($this->categoryFilter->getFieldName($qb)));

        return $categoryExpression;
    }
}
