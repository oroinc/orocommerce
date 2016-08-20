<?php

namespace Oro\Bundle\AccountBundle\Entity\Visibility\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityRepository extends EntityRepository
{
    /**
     * Update to 'config' ProductVisibility for products without category with fallback to 'category'.
     *
     * @param InsertFromSelectQueryExecutor $executor
     * @param Product|null $product
     */
    public function setToDefaultWithoutCategory(InsertFromSelectQueryExecutor $executor, Product $product = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                [
                    'product.id',
                    'website.id',
                    (string)$qb->expr()->literal(ProductVisibility::CONFIG)
                ]
            )
            ->from('OroProductBundle:Product', 'product')
            ->innerJoin(
                'OroWebsiteBundle:Website',
                'website',
                Join::WITH,
                $qb->expr()->eq(1, 1)
            )
            ->leftJoin(
                'OroCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->leftJoin(
                'OroAccountBundle:Visibility\ProductVisibility',
                'productVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('productVisibility.product', 'product'),
                    $qb->expr()->eq('productVisibility.website', 'website')
                )
            )
            ->where($qb->expr()->isNull('productVisibility.id'))
            ->andWhere($qb->expr()->isNull('category.id'));

        if ($product) {
            $qb->andWhere('product = :product')
                ->setParameter('product', $product);
        }

        $executor->execute(
            'OroAccountBundle:Visibility\ProductVisibility',
            ['product', 'website', 'visibility'],
            $qb
        );
    }
}
