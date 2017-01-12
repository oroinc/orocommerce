<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - scope
 *  - product
 */
class CustomerProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param Product $product
     * @param Scope $scope
     * @return null|CustomerProductVisibilityResolved
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
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Product $product
     * @param Category|null $category
     */
    public function insertByProduct(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category = null
    ) {
        $visibilityMap = [
            CustomerProductVisibility::HIDDEN => [
                'visibility' => CustomerProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CustomerProductVisibilityResolved::SOURCE_STATIC,
            ],
            CustomerProductVisibility::VISIBLE => [
                'visibility' => CustomerProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CustomerProductVisibilityResolved::SOURCE_STATIC,
            ],
            CustomerProductVisibility::CURRENT_PRODUCT => [
                'visibility' => CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => CustomerProductVisibilityResolved::SOURCE_STATIC,
            ],
        ];

        $fields = ['sourceProductVisibility', 'product', 'scope', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
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

            $insertExecutor->execute(
                $this->getEntityName(),
                $fields,
                $qb
            );
        }

        if ($category) {
            $fields[] = 'category';
            $this->insertByCustomerCategoryVisibility($insertExecutor, $product, $category, $fields);
            $this->insertByCustomerGroupCategoryVisibility($insertExecutor, $product, $category, $fields);
            $this->insertByCategoryVisibility($insertExecutor, $product, $category, $fields);
        }
    }

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
        $configValue = CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select(
            'apv.id',
            'IDENTITY(apv.scope)',
            'IDENTITY(apv.product)',
            'COALESCE(acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)CustomerProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id'
        )
        ->innerJoin('apv.scope', 'scope')
        ->innerJoin('scope.customer', 'customer')
        ->innerJoin('OroCatalogBundle:Category', 'category', 'WITH', 'apv.product MEMBER OF category.products')
        ->leftJoin('OroScopeBundle:Scope', 'acvr_scope', 'WITH', 'acvr_scope.customer = scope.customer')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved',
            'acvr',
            'WITH',
            'acvr.scope = acvr_scope AND acvr.category = category'
        )
        ->leftJoin('OroScopeBundle:Scope', 'agcvr_scope', 'WITH', 'agcvr_scope.customerGroup = customer.group')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.category = category AND agcvr.scope = agcvr_scope'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->andWhere('apv.visibility = :categoryVisibility')
        ->setParameter('categoryVisibility', CustomerProductVisibility::CATEGORY);
        $scopeManager->getCriteriaForRelatedScopes(CustomerCategoryVisibility::VISIBILITY_TYPE, [])
            ->applyToJoin($qb, 'acvr_scope');
        $scopeManager->getCriteriaForRelatedScopes(CustomerGroupCategoryVisibility::VISIBILITY_TYPE, [])
            ->applyToJoin($qb, 'agcvr_scope');

        if ($scope) {
            $qb->andWhere('apv.scope = :scope')
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
    public function insertStatic(InsertFromSelectQueryExecutor $insertExecutor, Scope $scope = null)
    {
        $visibility = <<<VISIBILITY
CASE WHEN apv.visibility = :visible
    THEN :cacheVisible
ELSE
    CASE WHEN apv.visibility = :currentProduct
        THEN :cacheFallbackAll
    ELSE :cacheHidden
    END
END
VISIBILITY;
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
            ->createQueryBuilder('apv');
        $queryBuilder
            ->select(
                'apv.id',
                'IDENTITY(apv.scope)',
                'IDENTITY(apv.product)',
                $visibility,
                (string)BaseProductVisibilityResolved::SOURCE_STATIC
            )
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('apv.visibility', ':visible'),
                $queryBuilder->expr()->eq('apv.visibility', ':hidden'),
                $queryBuilder->expr()->eq('apv.visibility', ':currentProduct')
            ))
            ->setParameter('visible', CustomerProductVisibility::VISIBLE)
            ->setParameter('hidden', CustomerProductVisibility::HIDDEN)
            ->setParameter('currentProduct', CustomerProductVisibility::CURRENT_PRODUCT)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN)
            ->setParameter('cacheFallbackAll', CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL);

        if ($scope) {
            $queryBuilder->andWhere('apv.scope = :scope')
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
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Product $product
     * @param Category $category
     * @param array $fields
     */
    protected function insertByCustomerCategoryVisibility(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category,
        array $fields
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'IDENTITY(apv.scope)',
            'acvr.visibility',
            (string)CustomerProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(acvr.category)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroCustomerBundle:Customer', 'ac', 'WITH', 'scope.customer = ac')

            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved',
                'acvr',
                'WITH',
                'acvr.category = :category'
            )
            ->innerJoin(
                'OroScopeBundle:Scope',
                'acs',
                'WITH',
                'acvr.scope = acs AND acs.customer = scope.customer'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY);

        $insertExecutor->execute($this->getEntityName(), $fields, $qb);
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Product $product
     * @param Category $category
     * @param array $fields
     */
    protected function insertByCustomerGroupCategoryVisibility(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category,
        array $fields
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
            ->createQueryBuilder('apv');

        $parentAlias = $this->getRootAlias($qb);
        $subQueryBuilder = $this->getSubQueryOfExistsVisibilities($parentAlias);

        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'IDENTITY(apv.scope)',
            'agcvr.visibility',
            (string)CustomerProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(agcvr.category)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroCustomerBundle:Customer', 'ac', 'WITH', 'scope.customer = ac')

            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved',
                'agcvr',
                'WITH',
                'agcvr.category = :category'
            )
            ->innerJoin(
                'OroScopeBundle:Scope',
                'gcs',
                'WITH',
                'agcvr.scope = gcs AND gcs.customerGroup = scope.customerGroup'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->andWhere($qb->expr()->not($qb->expr()->exists($subQueryBuilder->getQuery()->getDQL())))
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY);

        $insertExecutor->execute($this->getEntityName(), $fields, $qb);
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertExecutor
     * @param Product $product
     * @param Category $category
     * @param array $fields
     */
    protected function insertByCategoryVisibility(
        InsertFromSelectQueryExecutor $insertExecutor,
        Product $product,
        Category $category,
        array $fields
    ) {
        $configValue = CustomerProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\CustomerProductVisibility')
            ->createQueryBuilder('apv');

        $parentAlias = $this->getRootAlias($qb);
        $subQueryBuilder = $this->getSubQueryOfExistsVisibilities($parentAlias);

        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'IDENTITY(apv.scope)',
            'COALESCE(cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)CustomerProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(cvr.category)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroCustomerBundle:Customer', 'ac', 'WITH', 'scope.customer = ac')

            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'cvr.category = :category'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->andWhere($qb->expr()->not($qb->expr()->exists($subQueryBuilder->getQuery()->getDQL())))
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', CustomerProductVisibility::CATEGORY);

        $insertExecutor->execute($this->getEntityName(), $fields, $qb);
    }

    /**
     * @param QueryBuilder $qb
     * @return string
     */
    protected function getRootAlias(QueryBuilder $qb)
    {
        $aliases = $qb->getRootAliases();

        return reset($aliases);
    }

    /**
     * @param $parentAlias
     * @return QueryBuilder
     */
    protected function getSubQueryOfExistsVisibilities($parentAlias)
    {
        $subQueryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->createQueryBuilder('apvr');
        $subQueryBuilder->where(
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq('apvr.product', ':product'),
                $subQueryBuilder->expr()->eq('apvr.scope', $parentAlias . '.scope')
            )
        );

        return $subQueryBuilder;
    }
}
