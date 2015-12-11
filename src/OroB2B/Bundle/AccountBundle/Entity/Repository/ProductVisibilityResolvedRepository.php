<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ProductVisibilityResolvedRepository extends EntityRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $executor
     * @param string $cacheVisibility
     * @param array $categories
     */
    public function insertByCategory(InsertFromSelectQueryExecutor $executor, $cacheVisibility, array $categories)
    {
        $queryBuilder = $this->getEntityManager()
            ->getRepository('OroB2BCatalogBundle:Category')
            ->createQueryBuilder('category')
            ->select([
                'website.id as websiteId',
                'product.id as productId',
                (string)$cacheVisibility,
                (string)BaseProductVisibilityResolved::SOURCE_CATEGORY,
                'category.id as categoryId'
            ])
            ->innerJoin('OroB2BProductBundle:Product', 'product', Join::WITH, 'product MEMBER OF category.products')
            ->innerJoin('OroB2BWebsiteBundle:Website', 'website')
            ->where('category.id in (:ids)')
            ->setParameter('ids', $categories)
        ;

        $executor->execute($this->getClassName(), [
            'website', 'product', 'visibility', 'source', 'categoryId'
        ], $queryBuilder);
    }

    /**
     * @param string $baseVisibility
     * @param string $cacheVisibility
     * @return mixed
     */
    public function updateFromBaseTable($cacheVisibility, $baseVisibility)
    {
        $qb = $this->createQueryBuilder('pvr');
        return $qb->update()
            ->set('pvr.visibility', $cacheVisibility)
            ->set('pvr.source', BaseProductVisibilityResolved::SOURCE_STATIC)
            ->set('pvr.categoryId', ':category_id')
            ->setParameter('category_id', null)
            ->where($this->getWhereExpr($qb))
            ->setParameter('visibility', $baseVisibility)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param string $visibility
     */
    public function deleteByVisibility($visibility)
    {
        $qb = $this->createQueryBuilder('pvr');
        $qb->delete()
            ->where($this->getWhereExpr($qb))
            ->setParameter('visibility', $visibility)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @return int
     */
    public function clearTable()
    {
        return $this->createQueryBuilder('pvr')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * @param Product $product
     * @param Website $website
     * @return null|ProductVisibilityResolved
     */
    public function findByPrimaryKey(Product $product, Website $website)
    {
        return $this->findOneBy(['website' => $website, 'product' => $product]);
    }

    /**
     * @param QueryBuilder $qb
     * @return \Doctrine\ORM\Query\Expr\Andx
     */
    protected function getWhereExpr(QueryBuilder $qb)
    {
        return $qb->expr()->andX(
            $qb->expr()->in(
                'IDENTITY(pvr.product)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(pv_1.product)')
                    ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_1')
                    ->where('IDENTITY(pv_1.product) = IDENTITY(pvr.product)')
                    ->andWhere('IDENTITY(pv_1.website) = IDENTITY(pvr.website)')
                    ->andWhere('pv_1.visibility = :visibility')
                    ->getQuery()
                    ->getDQL()
            ),
            $qb->expr()->in(
                'IDENTITY(pvr.website)',
                $this->getEntityManager()->createQueryBuilder()
                    ->select('IDENTITY(pv_2.website)')
                    ->from('OroB2BAccountBundle:Visibility\ProductVisibility', 'pv_2')
                    ->where('IDENTITY(pv_2.product) = IDENTITY(pvr.product)')
                    ->andWhere('IDENTITY(pv_2.website) = IDENTITY(pvr.website)')
                    ->getQuery()
                    ->getDQL()
            )
        );
    }
}
