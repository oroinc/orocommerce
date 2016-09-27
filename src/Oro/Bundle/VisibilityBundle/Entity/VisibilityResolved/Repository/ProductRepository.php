<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - product
 */
class ProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Scope|null $scope
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $executor, Scope $scope)
    {
        var_dump("insertByCategory");
        $qb = $this->getEntityManager()
            ->getRepository('OroCatalogBundle:Category')
            ->createQueryBuilder('category');

        $scopeJoinCondition = 'scope = :scope';
        $qb->setParameter('scope', $scope);

        $configValue = ProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb->select([
            'scope.id',
            'product.id',
            'COALESCE(cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)ProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id',
        ])
        ->innerJoin('category.products', 'product')
        ->innerJoin('OroScopeBundle:Scope', 'scope', Join::WITH, $scopeJoinCondition)
        ->leftJoin(
            'OroVisibilityBundle:Visibility\ProductVisibility',
            'pv',
            'WITH',
            'IDENTITY(pv.product) = product.id AND IDENTITY(pv.scope) = scope.id'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->where('pv.id is null');

        $executor->execute(
            $this->getClassName(),
            ['scope', 'product', 'visibility', 'source', 'category'],
            $qb
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Scope|null $scope
     * @param Product|null $product
     */
    public function insertStatic(
        InsertFromSelectQueryExecutor $executor,
        Scope $scope = null,
        $product = null
    ) {
        $visibilityCondition = sprintf(
            "CASE WHEN pv.visibility = '%s' THEN %s ELSE %s END",
            ProductVisibility::VISIBLE,
            ProductVisibilityResolved::VISIBILITY_VISIBLE,
            ProductVisibilityResolved::VISIBILITY_HIDDEN
        );

        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\ProductVisibility')
            ->createQueryBuilder('pv')
            ->select(
                'pv.id',
                'IDENTITY(pv.scope)',
                'IDENTITY(pv.product)',
                $visibilityCondition,
                (string)ProductVisibilityResolved::SOURCE_STATIC
            )
            ->where('pv.visibility != :config')
            ->setParameter('config', ProductVisibility::CONFIG);

        if ($scope) {
            $qb->andWhere('pv.scope = :scope')
                ->setParameter('scope', $scope);
        }
        if ($product) {
            $qb->andWhere('pv.product = :product')
                ->setParameter('product', $product);
        }

        $executor->execute(
            $this->getClassName(),
            ['sourceProductVisibility', 'scope', 'product', 'visibility', 'source'],
            $qb
        );
    }

    /**
     * @param Product $product
     * @param Scope $scope
     * @return null|ProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Scope $scope)
    {
        return $this->findOneBy(['scope' => $scope, 'product' => $product]);
    }

    /**
     * @param Product $product
     */
    public function deleteByProduct(Product $product)
    {
        $this->createQueryBuilder('productVisibility')
            ->delete()
            ->where('productVisibility.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param Product $product
     * @param int $visibility
     * @param Scope $scope
     * @param Category $category
     */
    public function insertByProduct(
        InsertFromSelectQueryExecutor $executor,
        Product $product,
        $visibility,
        Scope $scope,
        Category $category
    ) {
        $this->insertStatic($executor, null, $product);

        $qb = $this->getVisibilitiesByCategoryQb($visibility, [$category->getId()], $scope);
        $qb->andWhere('product = :product')
            ->setParameter('product', $product);

        $executor->execute(
            $this->getClassName(),
            ['scope', 'product', 'visibility', 'source', 'category'],
            $qb
        );
    }

    /**
     * @param int $visibility
     * @param array $categoryIds
     * @param Scope $scope
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getVisibilitiesByCategoryQb($visibility, array $categoryIds, Scope $scope)
    {
        $qb = $this->getEntityManager()
            ->getRepository('OroCatalogBundle:Category')
            ->createQueryBuilder('category');

        $qb->select([
            'scope.id as w_id',
            'product.id as p_id',
            (string)$visibility,
            (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id as c_id',
        ])
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroScopeBundle:Scope',
                'scope',
                Join::WITH,
                'scope.id = :scopeId'
            )
            ->leftJoin(
                'OroVisibilityBundle:Visibility\ProductVisibility',
                'pv',
                Join::WITH,
                'IDENTITY(pv.product) = product.id AND IDENTITY(pv.scope) = scope.id'
            )
            ->where('pv.id is null')
            ->andWhere('category.id in (:ids)')
            ->setParameter('ids', $categoryIds)
            ->setParameter('scopeId', $scope->getId());

        return $qb;
    }
}
