<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

class AccountGroupProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param integer $cacheVisibility
     * @param integer[] $categories
     * @param integer $accountGroupId
     */
    public function insertByCategory(
        InsertFromSelectQueryExecutor $insertFromSelect,
        $cacheVisibility,
        $categories,
        $accountGroupId
    ) {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select(
                'IDENTITY(agpv.website)',
                'product.id',
                (string)$accountGroupId,
                (string)$cacheVisibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id'
            )
            ->innerJoin('category.products', 'product')
            ->innerJoin(
                'OroB2BAccountBundle:Visibility\AccountGroupProductVisibility',
                'agpv',
                Join::WITH,
                'agpv.product = product AND agpv.visibility = :category AND IDENTITY(agpv.accountGroup) = :accGroupId'
            )
            ->where('category.id in (:ids)')
            ->setParameter('ids', $categories)
            ->setParameter('accGroupId', $accountGroupId)
            ->setParameter('category', AccountGroupProductVisibility::CATEGORY);

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'accountGroup',
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
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility')
            ->createQueryBuilder('agpv')
            ->select(
                [
                    'IDENTITY(agpv.website)',
                    'IDENTITY(agpv.product)',
                    'IDENTITY(agpv.accountGroup)',
                    'CASE WHEN agpv.visibility = :visible THEN :cacheVisible ELSE :cacheHidden END',
                    (string)BaseProductVisibilityResolved::SOURCE_STATIC,
                ]
            )
            ->where('agpv.visibility = :visible OR agpv.visibility = :hidden')
            ->setParameter('visible', AccountGroupProductVisibility::VISIBLE)
            ->setParameter('hidden', AccountGroupProductVisibility::HIDDEN)
            ->setParameter('cacheVisible', BaseProductVisibilityResolved::VISIBILITY_VISIBLE)
            ->setParameter('cacheHidden', BaseProductVisibilityResolved::VISIBILITY_HIDDEN);

        $insertFromSelect->execute(
            $this->getClassName(),
            [
                'website',
                'product',
                'accountGroup',
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
        return $this->createQueryBuilder('agpvr')
            ->delete()
            ->getQuery()
            ->execute();
    }
}
