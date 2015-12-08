<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountProductVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

class AccountProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param $cacheVisibility
     * @param $categories
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
                [
                    'website.id as websiteId',
                    'product.id as productId',
                    (string)$accountId,
                    (string)$cacheVisibility,
                    (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                    'category.id as categoryId',
                ]
            )
            ->innerJoin('OroB2BProductBundle:Product', 'product', Join::WITH, 'product MEMBER OF category.products')
            ->innerJoin('OroB2BWebsiteBundle:Website', 'website')
            ->where('category.id in (:ids)')
            ->setParameter('ids', $categories);
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
     * @param string $baseVisibility
     * @param string $cacheVisibility
     * @return mixed
     */
    public function updateFromBaseTable($cacheVisibility, $baseVisibility)
    {
        $qb = $this->createQueryBuilder('apvr');

        return $qb->update()
            ->set('apvr.visibility', $cacheVisibility)
            ->set('apvr.source', BaseProductVisibilityResolved::SOURCE_STATIC)
            ->set('apvr.categoryId', ':category_id')
            ->setParameter('category_id', null)
            ->where($this->getWhereExpr($qb))
            ->setParameter('visibility', $baseVisibility)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $cacheVisibility
     * @return mixed
     */
    public function updateFromBaseTableForCurrentProduct($cacheVisibility)
    {
        $qb = $this->createQueryBuilder('apvr');

        return $qb->update()
            ->set('apvr.visibility', $cacheVisibility)
            ->set('apvr.source', BaseProductVisibilityResolved::SOURCE_STATIC)
            ->set('apvr.categoryId', ':category_id')
            ->where($this->getWhereExprForVisibilityToAll($qb))
            ->setParameter('category_id', null)
            ->setParameter('visibility', $cacheVisibility)
            ->setParameter('source_visibility', AccountProductVisibility::CURRENT_PRODUCT)
            ->getQuery()
            ->execute();
    }

    /**
     * @param $visibility
     */
    public function deleteByVisibility($visibility)
    {
        $qb = $this->createQueryBuilder('apvr');
        $qb->delete()
            ->where($this->getWhereExpr($qb))
            ->setParameter('visibility', $visibility)
            ->getQuery()
            ->execute();
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

    /**
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Andx
     */
    protected function getWhereExprForVisibilityToAll(QueryBuilder $qb)
    {
        return $qb->expr()->andX(
            $qb->expr()->in(
                'IDENTITY(apvr.product)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_1.product)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_1')
                    ->innerJoin(
                        'OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved',
                        'pvr',
                        Join::WITH,
                        'IDENTITY(apv_1.product) = IDENTITY(pvr.product)
                        and IDENTITY(apv_1.website) = IDENTITY(pvr.website)'
                    )
                    ->where('IDENTITY(apv_1.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_1.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_1.account) = IDENTITY(apvr.account)')
                    ->andWhere('apv_1.visibility = :source_visibility')
                    ->andWhere("pvr.visibility = :visibility")
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(apvr.website)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_2.website)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_2')
                    ->where('IDENTITY(apv_2.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_2.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_2.account) = IDENTITY(apvr.account)')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(apvr.account)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_3.account)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_3')
                    ->where('IDENTITY(apv_3.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_3.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_3.account) = IDENTITY(apvr.account)')
                    ->getQuery()
                    ->getDQL()
            )
        );
    }

    /**
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Andx
     */
    protected function getWhereExpr(QueryBuilder $qb)
    {
        return $qb->expr()->andX(
            $qb->expr()->in(
                'IDENTITY(apvr.product)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_1.product)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_1')
                    ->where('IDENTITY(apv_1.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_1.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_1.account) = IDENTITY(apvr.account)')
                    ->andWhere('apv_1.visibility = :visibility')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(apvr.website)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_2.website)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_2')
                    ->where('IDENTITY(apv_2.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_2.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_2.account) = IDENTITY(apvr.account)')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(apvr.account)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(apv_3.account)')
                    ->from('OroB2BAccountBundle:Visibility\AccountProductVisibility', 'apv_3')
                    ->where('IDENTITY(apv_3.product) = IDENTITY(apvr.product)')
                    ->andWhere('IDENTITY(apv_3.website) = IDENTITY(apvr.website)')
                    ->andWhere('IDENTITY(apv_3.account) = IDENTITY(apvr.account)')
                    ->getQuery()
                    ->getDQL()
            )
        );
    }
}
