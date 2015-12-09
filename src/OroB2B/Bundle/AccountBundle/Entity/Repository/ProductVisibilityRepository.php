<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

class ProductVisibilityRepository extends EntityRepository
{
    /**
     * Update ToConfig ProductVisibility for Products without category with FallbackToCategory
     * @param InsertFromSelectQueryExecutor $executor
     */
    public function updateToConfigProductVisibility(InsertFromSelectQueryExecutor $executor)
    {
        $qb = $this->getEntityManager()
            ->getRepository('OroB2BProductBundle:Product')
            ->createQueryBuilder('product');

        $qb->select("product.id as productId, website.id as websiteId, '". ProductVisibility::CONFIG ."'")
            ->innerJoin('OroB2BWebsiteBundle:Website', 'website', Join::WITH, '1 = 1')
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                'product MEMBER OF category.products'
            )
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\ProductVisibility',
                'productVisibility',
                Join::WITH,
                $qb->expr()->eq('productVisibility.product', 'product')
            )
            ->where($qb->expr()->isNull('productVisibility.id'))
            ->andWhere($qb->expr()->isNull('category.id'));

        $executor->execute(
            'OroB2BAccountBundle:Visibility\ProductVisibility',
            ['product', 'website', 'visibility'],
            $qb
        );
    }
}
