<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @param Account $account
     * @param Product $product
     * @param Website $website
     * @return null|AccountProductVisibilityResolved
     */
    public function findByPrimaryKey(Account $account, Product $product, Website $website)
    {
        return $this->findOneBy(['account' => $account, 'website' => $website, 'product' => $product]);
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

        $fields = ['sourceProductVisibility', 'product', 'website', 'account', 'visibility', 'source'];

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
                ->createQueryBuilder('productVisibility');

            $qb->select([
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.website)',
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
            $configValue = AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
            $qb = $this->getEntityManager()
                ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
                ->createQueryBuilder('apv');
            $qb->select([
                'apv.id',
                'IDENTITY(apv.product)',
                'IDENTITY(apv.website)',
                'IDENTITY(apv.account)',
                'COALESCE(' .
                    'acvr.visibility, agcvr.visibility, cvr.visibility, ' . $qb->expr()->literal($configValue) .
                ')',
                (string)AccountProductVisibilityResolved::SOURCE_CATEGORY,
                (string)$category->getId()
            ])
            ->innerJoin('apv.account', 'account')
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved',
                'acvr',
                'WITH',
                'acvr.account = apv.account AND acvr.category = :category'
            )
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved',
                'agcvr',
                'WITH',
                'agcvr.accountGroup = account.group AND agcvr.category = :category'
            )
            ->leftJoin(
                'OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved',
                'cvr',
                'WITH',
                'cvr.category = :category'
            )
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->setParameter('category', $category)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountProductVisibility::CATEGORY);

            $fields[] = 'category';
            $insertFromSelect->execute($this->getEntityName(), $fields, $qb);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $insertFromSelect, Website $website = null)
    {
        $configValue = AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG;
        $qb = $this->getEntityManager()
            ->getRepository('OroVisibilityBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $qb->select(
            'apv.id',
            'IDENTITY(apv.website)',
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

        if ($website) {
            $qb->andWhere('apv.website = :website')
                ->setParameter('website', $website);
        }

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'website',
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
     * @param Website|null $website
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, Website $website = null)
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
                'IDENTITY(apv.website)',
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

        if ($website) {
            $queryBuilder->andWhere('apv.website = :website')
                ->setParameter('website', $website);
        }
        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'sourceProductVisibility',
                'website',
                'product',
                'account',
                'visibility',
                'source',
            ],
            $queryBuilder
        );
    }
}
