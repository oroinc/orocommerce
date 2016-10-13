<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

/**
 * Composite primary key fields order:
 *  - account
 *  - website
 *  - product
 */
class AccountProductRepository extends AbstractVisibilityRepository
{
    use BasicOperationRepositoryTrait;

    /**
     * @param Product $product
     * @param Scope $scope
     * @return null|AccountProductVisibilityResolved
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
     * @param Product $product
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Category|null $category
     */
    public function insertByProduct(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        Category $category = null
    ) {
        $visibilityMap = [
            AccountProductVisibility::HIDDEN => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
            ],
            AccountProductVisibility::VISIBLE => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
            ],
            AccountProductVisibility::CURRENT_PRODUCT => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
            ],
        ];

        $fields = ['sourceProductVisibility', 'product', 'scope', 'account', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
                ->createQueryBuilder('productVisibility');

            $qb->select([
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.scope)',
                'IDENTITY(productVisibility.account)',
                (string)$productVisibility['visibility'],
                (string)$productVisibility['source'],
            ])
            ->where('productVisibility.product = :product')
            ->andWhere('productVisibility.visibility = :visibility')
            ->setParameter('product', $product)
            ->setParameter('visibility', $visibility);

            $insertFromSelect->execute(
                $this->getEntityName(),
                $fields,
                $qb
            );
        }

        if ($category) {
            $fields[] = 'category';
            $this->insertByAccCtgrVsbResolv($product, $insertFromSelect, $category, $fields);
            $this->insertByAccGrpCtgrVsbResolv($product, $insertFromSelect, $category, $fields);
            $this->insertByCtgrVsbResolv($product, $insertFromSelect, $category, $fields);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $insertFromSelect, Scope $scope = null)
    {
        $configValue = AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select(
            'apv.id',
            'IDENTITY(apv.scope)',
            'IDENTITY(apv.product)',
            'IDENTITY(apv.account)',
            'COALESCE(acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) . ')',
            (string)AccountProductVisibilityResolved::SOURCE_CATEGORY,
            'category.id'
        )
        ->innerJoin('apv.account', 'account')
        ->innerJoin('OroCatalogBundle:Category', 'category', 'WITH', 'apv.product MEMBER OF category.products')
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
            'acvr',
            'WITH',
            'acvr.account = apv.account AND acvr.category = category'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
            'agcvr',
            'WITH',
            'agcvr.accountGroup = account.group AND agcvr.category = category'
        )
        ->leftJoin(
            'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
            'cvr',
            'WITH',
            'cvr.category = category'
        )
        ->andWhere('apv.visibility = :categoryVisibility')
        ->setParameter('categoryVisibility', AccountProductVisibility::CATEGORY);

        if ($scope) {
            $qb->andWhere('apv.scope = :scope')
                ->setParameter('scope', $scope);
        }

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'scope',
                'product',
                'account',
                'visibility',
                'source',
                'category',
            ],
            $qb
        );
    }

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Scope|null $scope
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, Scope $scope = null)
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
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $queryBuilder
            ->select(
                'apv.id',
                'IDENTITY(apv.scope)',
                'IDENTITY(apv.product)',
                'IDENTITY(apv.account)',
                $visibility,
                (string)BaseProductVisibilityResolved::SOURCE_STATIC
            )
            ->where($queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('apv.visibility', ':visible'),
                $queryBuilder->expr()->eq('apv.visibility', ':hidden'),
                $queryBuilder->expr()->eq('apv.visibility', ':currentProduct')
            ))
            ->setParameter('visible', AccountProductVisibility::VISIBLE)
            ->setParameter('hidden', AccountProductVisibility::HIDDEN)
            ->setParameter('currentProduct', AccountProductVisibility::CURRENT_PRODUCT)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN)
            ->setParameter('cacheFallbackAll', AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL);

        if ($scope) {
            $queryBuilder->andWhere('apv.scope = :scope')
                ->setParameter('scope', $scope);
        }
        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'scope',
                'product',
                'account',
                'visibility',
                'source',
            ],
            $queryBuilder
        );
    }

    /**
     * @param Product $product
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Category $category
     * @param array $fields
     */
    public function insertByAccCtgrVsbResolv(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        Category $category,
        array $fields
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'acvr.visibility',
            (string)AccountProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(apv.scope)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroAccountBundle:Account', 'ac', 'WITH', 'scope.account = ac.account')

            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
                'acvr',
                'WITH',
                'acvr.category = :category'
            )
            ->innerJoin(
                'OroScopeBundle:Scope',
                'acs',
                'WITH',
                'acvr.scope = acs.scope AND acs.account = scope.account'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY);

        $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
    }

    /**
     * @param Product $product
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Category $category
     * @param array $fields
     */
    public function insertByAccGrpCtgrVsbResolv(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        Category $category,
        array $fields
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'agcvr.visibility',
            (string)AccountProductVisibilityResolved::SOURCE_CATEGORY,
            'IDENTITY(apv.scope)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroAccountBundle:Account', 'ac', 'WITH', 'scope.account = ac.account')

            ->innerJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                'agcvr',
                'WITH',
                'agcvr.category = :category'
            )
            ->innerJoin(
                'OroScopeBundle:Scope',
                'gcs',
                'WITH',
                'agcvr.scope = gcs.scope AND gcs.accountgroup = scope.accountgroup'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY);

        $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
    }

    /**
     * @param Product $product
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Category $category
     * @param array $fields
     */
    public function insertByCtgrVsbResolv(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        Category $category,
        array $fields
    ) {
        $configValue = AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;

        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');

        $parentAlias = $this->getRootAlias($qb);
        $subQueryBuilder = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->createQueryBuilder('apvr');
        $subQueryBuilder->where(
            $subQueryBuilder->expr()->andX(
                $subQueryBuilder->expr()->eq('apvr.product', ':apvr_product'),
                $subQueryBuilder->expr()->eq('relation.priceList', $parentAlias.'.scope')
            )
        );

        $qb->select([
            'apv.id',
            'IDENTITY(apv.product)',
            'COALESCE(' . 'cvr.visibility' . $qb->expr()->literal($configValue) .
            ')',
            'IDENTITY(apv.scope)',
        ])
            ->innerJoin('apv.scope', 'scope')
            ->innerJoin('OroAccountBundle:Account', 'ac', 'WITH', 'scope.account = ac.account')

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
            ->setParameter('apvr_product', $product)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY);

        $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
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
}
