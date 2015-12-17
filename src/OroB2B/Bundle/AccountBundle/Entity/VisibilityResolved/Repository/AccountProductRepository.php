<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;
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
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $cacheVisibility
     * @param integer[] $categories
     * @param integer $accountId
     * @param Website|null $website
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountId,
        Website $website = null
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

        if ($website) {
            $queryBuilder->andWhere('apv.website = :website')
                ->setParameter('website', $website);
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
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param Website|null $website
     */
    public function insertStatic(
        InsertFromSelectQueryExecutor $insertFromSelect,
        Website $website = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv')
            ->select(
                [
                    'IDENTITY(apv.website)',
                    'IDENTITY(apv.product)',
                    'IDENTITY(apv.account)',
                    'CASE WHEN apv.visibility = :visible THEN :cacheVisible ELSE :cacheHidden END',
                    (string)BaseProductVisibilityResolved::SOURCE_STATIC,
                ]
            )
            ->where('apv.visibility = :visible OR apv.visibility = :hidden')
            ->setParameter('visible', AccountProductVisibility::VISIBLE)
            ->setParameter('hidden', AccountProductVisibility::HIDDEN)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        if ($website) {
            $queryBuilder->andWhere('apv.website = :website')
                ->setParameter('website', $website);
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

    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $configValue
     * @param Website|null $website
     */
    public function insertForCurrentProductFallback(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $configValue,
        Website $website = null
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BAccountBundle:Visibility\AccountProductVisibility')
            ->createQueryBuilder('apv')
            ->select(
                [
                    'IDENTITY(apv.website)',
                    'IDENTITY(apv.product)',
                    'IDENTITY(apv.account)',
                    'CASE WHEN pvr.visibility IS NULL THEN :config_value ELSE pvr.visibility END',
                    (string)BaseProductVisibilityResolved::SOURCE_STATIC,
                ]
            )
            ->leftJoin(
                'OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved',
                'pvr',
                Join::WITH,
                'pvr.product = apv.product AND pvr.website = apv.website'
            )
            ->where('apv.visibility = :current_product')
            ->setParameter('current_product', AccountProductVisibility::CURRENT_PRODUCT)
            ->setParameter('config_value', $configValue);

        if ($website) {
            $queryBuilder->andWhere('apv.website = :website')
                ->setParameter('website', $website);
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

    /**
     * @param Account $account
     * @param Product $product
     * @param Website $website
     * @return null|AccountProductVisibilityResolved
     */
    public function findByPrimaryKey(
        Account $account,
        Product $product,
        Website $website
    ) {
        return $this->findOneBy(['account' => $account, 'website' => $website, 'product' => $product]);
    }

    /**
     * Set specified visibility to all resolved entities with fallback to current product
     *
     * @param Website $website
     * @param Product $product
     * @param int $visibility
     */
    public function updateCurrentProductRelatedEntities(
        Website $website,
        Product $product,
        $visibility
    ) {
        $affectedEntitiesDql = $this->getEntityManager()->createQueryBuilder()
            ->select('apv.id')
            ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv')
            ->andWhere('apv.website = :website')
            ->andWhere('apv.product = :product')
            ->andWhere('apv.visibility = :visibility')
            ->getQuery()
            ->getDQL();

        $this->createQueryBuilder('apvr')
            ->update('OroB2BAccountBundle:VisibilityResolved\AccountProductVisibilityResolved', 'apvr')
            ->set('apvr.visibility', $visibility)
            ->where('IDENTITY(apvr.sourceProductVisibility) IN (' . $affectedEntitiesDql . ')')
            ->setParameter('website', $website)
            ->setParameter('product', $product)
            ->setParameter('visibility', AccountProductVisibility::CURRENT_PRODUCT)
            ->getQuery()
            ->execute();
    }
}
