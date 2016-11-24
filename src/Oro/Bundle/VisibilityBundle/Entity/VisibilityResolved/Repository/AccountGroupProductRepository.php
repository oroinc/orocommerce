<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - product
 */
class AccountGroupProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param ScopeManager $scopeManager
     * @param Scope|null $scope
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertExecutor,
        ScopeManager $scopeManager,
        Scope $scope = null
    ) {
        $configValue = AccountGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
            ->createQueryBuilder('agpv');
        $qb->select(
            'agpv.id',
            'IDENTITY(agpv.scope)',
            'IDENTITY(agpv.product)',
            'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)AccountGroupProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id'
        )
        ->innerJoin('OroCatalogBundle:Category', 'category', 'WITH', 'agpv.product MEMBER OF category.products')
        ->innerJoin('agpv.scope', 'scope')
        ->leftJoin('OroScopeBundle:Scope', 'agcvr_scope', 'WITH', 'scope.accountGroup = agcvr_scope.accountGroup')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.scope = agcvr_scope AND agcvr.category = category'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->andWhere('agpv.visibility = :categoryVisibility')
        ->setParameter('categoryVisibility', AccountGroupProductVisibility::CATEGORY);
        $scopeCriteria = $scopeManager->getCriteriaForRelatedScopes(
            AccountGroupCategoryVisibility::VISIBILITY_TYPE,
            []
        );
        $scopeCriteria->applyToJoin($qb, 'agcvr_scope');
        if ($scope) {
            $qb->andWhere('agpv.scope = :scope')
                ->setParameter('scope', $scope);
        }

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

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Scope|null $scope
     */
    public function insertStatic(
        InsertFromSelectQueryExecutor $insertExecutor,
        Scope $scope = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
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
            ->setParameter('visible', AccountGroupProductVisibility::VISIBLE)
            ->setParameter('hidden', AccountGroupProductVisibility::HIDDEN)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        if ($scope) {
            $queryBuilder->andWhere('agpv.scope = :scope')
                ->setParameter('scope', $scope);
        }

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

    /**
     * @param Product $product
     */
    public function deleteByProduct(Product $product)
    {
        $this->createQueryBuilder('resolvedVisibility')
            ->delete()
            ->where('resolvedVisibility.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->execute();
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Product $product
     * @param Category $category
     * @internal param Product $product
     * @internal param null|Category $category
     */
    public function insertByProduct(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category = null
    ) {
        $visibilityMap = [
            AccountGroupProductVisibility::HIDDEN => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
            AccountGroupProductVisibility::VISIBLE => [
                'visibility' => AccountGroupProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountGroupProductVisibilityResolved::SOURCE_STATIC,
            ],
        ];

        $fields = ['sourceProductVisibility', 'product', 'scope', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
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

            $insertExecutor->execute($this->getEntityName(), $fields, $qb);
        }

        if ($category) {
            $configValue = AccountGroupProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountGroupProductVisibility')
                ->createQueryBuilder('productVisibility');
            $qb->select([
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.scope)',
                'COALESCE(agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
                (string)AccountGroupProductVisibilityResolved::SOURCE_CATEGORY,
                (string)$category->getId()
            ])
            ->join('productVisibility.scope', 'scope')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                'agcvr',
                'WITH',
                'agcvr.accountGroup = productVisibility.accountGroup AND agcvr.category = :category'
            )
            ->leftJoin('agcvr.scope', 'agcvr_scope')
            ->andWhere('agcvr.visibility IS NULL OR agcvr_scope.accountGroup = scope.accountGroup')
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
            ->setParameter('visibility', AccountGroupProductVisibility::CATEGORY);

            $fields[] = 'category';
            $insertExecutor->execute($this->getEntityName(), $fields, $qb);
        }
    }

    /**
     * @param Product $product
     * @param Scope $scope
     * @return null|AccountGroupProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Scope $scope)
    {
        return $this->findOneBy(['scope' => $scope, 'product' => $product]);
    }
}
