<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - product
 */
class CustomerGroupProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertExecutor,
        ScopeManager $scopeManager,
        Scope $scope = null
    ) {
        $qb = $this->getCustomerGroupProductVisibilityResolvedQueryBuilder($scopeManager, $scope);

        $insertExecutor->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'scope',
                'product',
                'visibility',
                'source',
                'category',
            ],
            $qb
        );
    }

    public function insertStatic(
        InsertFromSelectQueryExecutor $insertExecutor,
        Scope $scope = null
    ) {
        $queryBuilder = $this->getInsertStaticQueryBuilder($scope);

        $insertExecutor->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'scope',
                'product',
                'visibility',
                'source',
            ],
            $queryBuilder
        );
    }

    public function deleteByProduct(Product $product)
    {
        $this->createQueryBuilder('resolvedVisibility')
            ->delete()
            ->where('resolvedVisibility.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    public function insertByProduct(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category = null
    ) {
        $visibilityMap = [
            CustomerGroupProductVisibility::HIDDEN => [
                'visibility' => CustomerGroupProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CustomerGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
            CustomerGroupProductVisibility::VISIBLE => [
                'visibility' => CustomerGroupProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CustomerGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
        ];

        $fields = ['sourceProductVisibility', 'product', 'scope', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getInsertByProductVisibilityQueryBuilder($product, $visibility, $productVisibility);

            $insertExecutor->execute($this->getEntityName(), $fields, $qb);
        }

        if ($category) {
            $qb = $this->getInsertByProductCategoryQueryBuilder($product, $category);

            $fields[] = 'category';
            $insertExecutor->execute($this->getEntityName(), $fields, $qb);
        }
    }

    /**
     * @param Product $product
     * @param Scope $scope
     * @return null|CustomerGroupProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Scope $scope)
    {
        return $this->findOneBy(['scope' => $scope, 'product' => $product]);
    }

    /**
     * @param ScopeManager $scopeManager
     * @param Scope|null $scope
     * @return QueryBuilder
     */
    protected function getCustomerGroupProductVisibilityResolvedQueryBuilder(
        ScopeManager $scopeManager,
        Scope $scope = null
    ) {
        $configValue = CustomerGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupProductVisibility')
            ->createQueryBuilder('agpv');
        $qb->select(
            'agpv.id',
            'IDENTITY(agpv.scope)',
            'IDENTITY(agpv.product)',
            'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)CustomerGroupProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(product.category)'
        )
        ->innerJoin('agpv.product', 'product')
        ->innerJoin('agpv.scope', 'scope')
        ->leftJoin('OroScopeBundle:Scope', 'agcvr_scope', 'WITH', 'scope.customerGroup = agcvr_scope.customerGroup')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.scope = agcvr_scope AND agcvr.category = product.category'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = product.category'
        )
        ->andWhere('agpv.visibility = :categoryVisibility')
        ->setParameter('categoryVisibility', CustomerGroupProductVisibility::CATEGORY);
        $scopeCriteria = $scopeManager->getCriteriaForRelatedScopes(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            []
        );
        $scopeCriteria->applyToJoin($qb, 'agcvr_scope');
        if ($scope) {
            $qb->andWhere('agpv.scope = :scope')
                ->setParameter('scope', $scope);
        }

        return $qb;
    }

    /**
     * @param Scope|null $scope
     * @return QueryBuilder
     */
    protected function getInsertStaticQueryBuilder(Scope $scope = null)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupProductVisibility')
            ->createQueryBuilder('agpv')
            ->select(
                [
                    'agpv.id',
                    'IDENTITY(agpv.scope)',
                    'IDENTITY(agpv.product)',
                    'CASE WHEN agpv.visibility = :visible THEN :cacheVisible ELSE :cacheHidden END',
                    (string)BaseProductVisibilityResolved::SOURCE_STATIC,
                ]
            )
            ->where('agpv.visibility = :visible OR agpv.visibility = :hidden')
            ->setParameter('visible', CustomerGroupProductVisibility::VISIBLE)
            ->setParameter('hidden', CustomerGroupProductVisibility::HIDDEN)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        if ($scope) {
            $queryBuilder->andWhere('agpv.scope = :scope')
                ->setParameter('scope', $scope);
        }

        return $queryBuilder;
    }

    /**
     * @param Product $product
     * @param string $visibility
     * @param array $productVisibility
     * @return QueryBuilder
     */
    protected function getInsertByProductVisibilityQueryBuilder(Product $product, $visibility, array $productVisibility)
    {
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupProductVisibility')
            ->createQueryBuilder('productVisibility');
        $qb->select([
            'productVisibility.id',
            'IDENTITY(productVisibility.product)',
            'IDENTITY(productVisibility.scope)',
            (string)$productVisibility['visibility'],
            (string)$productVisibility['source'],
        ])
        ->where('productVisibility.product = :product')
        ->andWhere('productVisibility.visibility = :visibility')
        ->setParameter('product', $product)
        ->setParameter('visibility', $visibility);

        return $qb;
    }

    /**
     * @param Product $product
     * @param Category $category
     * @return QueryBuilder
     */
    protected function getInsertByProductCategoryQueryBuilder(Product $product, Category $category)
    {
        $configValue = CustomerGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupProductVisibility')
            ->createQueryBuilder('productVisibility');
        $qb->select([
            'productVisibility.id',
            'IDENTITY(productVisibility.product)',
            'IDENTITY(productVisibility.scope)',
            'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)CustomerGroupProductVisibilityResolved::SOURCE_CATEGORY,
            (string)$category->getId()
        ])
        ->join('productVisibility.scope', 'scope')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.category = :category'
        )
        ->leftJoin('agcvr.scope', 'agcvr_scope')
        ->andWhere('agcvr.visibility IS NULL OR agcvr_scope.customerGroup = scope.customerGroup')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = :category'
        )
        ->andWhere('productVisibility.product = :product')
        ->andWhere('productVisibility.visibility = :visibility')
        ->setParameter('category', $category)
        ->setParameter('product', $product)
        ->setParameter('visibility', CustomerGroupProductVisibility::CATEGORY);

        return $qb;
    }
}
