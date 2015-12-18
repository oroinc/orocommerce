<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
     * @param boolean $isCategoryVisible
     * @param Category|null $category
     */
    public function insertByProduct(
        Product $product,
        InsertFromSelectQueryExecutor $insertFromSelect,
        $isCategoryVisible,
        Category $category = null
    ) {
        $visibilityMap = [
            AccountProductVisibility::HIDDEN => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
            AccountProductVisibility::VISIBLE => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
            AccountProductVisibility::CURRENT_PRODUCT => [
                'visibility' => AccountProductVisibilityResolved::VISIBILITY_FALLBACK_TO_ALL,
                'source' => AccountProductVisibilityResolved::SOURCE_STATIC,
                'category' => null,
            ],
        ];
        if ($category) {
            $categoryVisibility = $isCategoryVisible ? AccountProductVisibilityResolved::VISIBILITY_VISIBLE :
                AccountProductVisibilityResolved::VISIBILITY_HIDDEN;
            $visibilityMap[AccountProductVisibility::CATEGORY] = [
                'visibility' => $categoryVisibility,
                'source' => AccountProductVisibilityResolved::SOURCE_CATEGORY,
                'category' => $category->getId(),
            ];
        }

        foreach ($visibilityMap as $visibility => $productVisibility) {
            $qb = $this->getEntityManager()
                ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
                ->createQueryBuilder('productVisibility');
            $fieldsInsert = ['sourceProductVisibility', 'product', 'website', 'account', 'visibility', 'source'];
            $fieldsSelect = [
                'productVisibility.id',
                'IDENTITY(productVisibility.product)',
                'IDENTITY(productVisibility.website)',
                'IDENTITY(productVisibility.account)',
                (string)$productVisibility['visibility'],
                (string)$productVisibility['source'],
            ];
            if ($productVisibility['category']) {
                $fieldsSelect[] = (string)$productVisibility['category'];
                $fieldsInsert[] = 'category';
            }
            $qb->select($fieldsSelect)
                ->where('productVisibility.product = :product')
                ->andWhere('productVisibility.visibility = :visibility')
                ->setParameter('product', $product)
                ->setParameter('visibility', $visibility);

            $insertFromSelect->execute(
                $this->getEntityName(),
                $fieldsInsert,
                $qb
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountId,
        $websiteId = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select(
                'IDENTITY(apv.website)',
                'product.id',
                (string)$accountId,
                (string)$cacheVisibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id'
            )
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountProductVisibility',
                'apv',
                Join::WITH,
                'apv.product = product AND apv.visibility = :category AND IDENTITY(apv.account) = :accountId'
            )
            ->where('category.id in (:ids)')
            ->setParameter('ids', $categories)
            ->setParameter('accountId', $accountId)
            ->setParameter('category', AccountProductVisibility::CATEGORY);
        if ($websiteId) {
            $queryBuilder->andWhere('IDENTITY(apv.website) = :website')
                ->setParameter('website', $websiteId);
        }
        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'account',
                'visibility',
                'source',
                'category',
            ],
            $queryBuilder
        );
    }

    /**
     * {@inheritdoc}
     */
    public function insertStatic(InsertFromSelectQueryExecutor $insertFromSelect, $websiteId = null)
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
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv');
        $queryBuilder
            ->select(
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

        if ($websiteId) {
            $queryBuilder->andWhere('IDENTITY(apv.website) = :website')
                ->setParameter('website', $websiteId);
        }
        $insertFromSelect->execute(
            $this->getClassName(),
            [
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
