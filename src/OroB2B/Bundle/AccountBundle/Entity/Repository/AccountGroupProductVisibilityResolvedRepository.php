<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseProductVisibilityResolved;

class AccountGroupProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelect
     * @param $cacheVisibility
     * @param $categories
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
                [
                    'website.id as websiteId',
                    'product.id as productId',
                    (string)$accountGroupId,
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
                'accountGroup',
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
        $qb = $this->createQueryBuilder('agpvr');

        return $qb->update()
            ->set('agpvr.visibility', $cacheVisibility)
            ->set('agpvr.source', BaseProductVisibilityResolved::SOURCE_STATIC)
            ->set('agpvr.categoryId', ':category_id')
            ->setParameter('category_id', null)
            ->where($this->getWhereExpr($qb))
            ->setParameter('visibility', $baseVisibility)
            ->getQuery()
            ->execute();
    }

    /**
     * @param $visibility
     */
    public function deleteByVisibility($visibility)
    {
        $qb = $this->createQueryBuilder('agpvr');
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
        return $this->createQueryBuilder('agpvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Andx
     */
    protected function getWhereExpr(QueryBuilder $qb)
    {
        return $qb->expr()->andX(
            $qb->expr()->in(
                'IDENTITY(agpvr.product)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(agpv_1.product)')
                    ->from('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility', 'agpv_1')
                    ->where('IDENTITY(agpv_1.product) = IDENTITY(agpvr.product)')
                    ->andWhere('IDENTITY(agpv_1.website) = IDENTITY(agpvr.website)')
                    ->andWhere('IDENTITY(agpv_1.accountGroup) = IDENTITY(agpvr.accountGroup)')
                    ->andWhere('agpv_1.visibility = :visibility')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(agpvr.website)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(agpv_2.website)')
                    ->from('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility', 'agpv_2')
                    ->where('IDENTITY(agpv_2.product) = IDENTITY(agpvr.product)')
                    ->andWhere('IDENTITY(agpv_2.website) = IDENTITY(agpvr.website)')
                    ->andWhere('IDENTITY(agpv_2.accountGroup) = IDENTITY(agpvr.accountGroup)')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(agpvr.accountGroup)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(agpv_3.accountGroup)')
                    ->from('OroB2BAccountBundle:Visibility\AccountGroupProductVisibility', 'agpv_3')
                    ->where('IDENTITY(agpv_3.product) = IDENTITY(agpvr.product)')
                    ->andWhere('IDENTITY(agpv_3.website) = IDENTITY(agpvr.website)')
                    ->andWhere('IDENTITY(agpv_3.accountGroup) = IDENTITY(agpvr.accountGroup)')
                    ->getQuery()
                    ->getDQL()
            )
        );
    }
}
