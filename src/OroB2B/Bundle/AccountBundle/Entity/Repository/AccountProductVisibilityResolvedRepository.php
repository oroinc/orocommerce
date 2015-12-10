<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

class AccountProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $cacheVisibility
     * @param integer[] $categories
     * @param integer $accountId
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountId
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

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'account',
                'visibility',
                'source',
                'categoryId',
            ],
            $queryBuilder
        );
    }


    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     */
    public function insertStatic(
        InsertFromSelectQueryExecutor $insertFromSelect
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
     */
    public function insertForCurrentProductFallback(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $configValue
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
     * @return int
     */
    public function clearTable()
    {
        return $this->createQueryBuilder('apvr')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
