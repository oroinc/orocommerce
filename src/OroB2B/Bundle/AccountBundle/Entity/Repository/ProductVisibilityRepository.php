<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityRepository extends EntityRepository
{
    /**
     * Update to 'config' ProductVisibility for products without category with fallback to 'category'.
     *
     * @param InsertFromSelectQueryExecutor $executor
     */
    public function setToDefaultWithoutCategory(InsertFromSelectQueryExecutor $executor)
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
            ->from('OroB2BProductBundle:Product', 'product')
            ->innerJoin(
                'OroB2BWebsiteBundle:Website',
                'website',
                Join::WITH,
                $qb->expr()->eq(1, 1)
            )
            ->leftJoin(
                'OroB2BCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->leftJoin(
                'OroB2BAccountBundle:Visibility\ProductVisibility',
                'productVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('productVisibility.product', 'product'),
                    $qb->expr()->eq('productVisibility.website', 'website')
                )
            )
            ->where($qb->expr()->isNull('productVisibility.id'))
            ->andWhere($qb->expr()->isNull('category.id'));

        $executor->execute(
            'OroB2BAccountBundle:Visibility\ProductVisibility',
            ['product', 'website', 'visibility'],
            $qb
        );
    }

    /**
     * @param Product $product
     * @param array $websites
     */
    public function setToDefaultWithoutCategoryByProduct(Product $product, array $websites)
    {
        foreach ($websites as $website) {
            $visibility = $this->findBy(['product' => $product, 'website' => $website]);
            if (!$visibility) {
                $visibility = new ProductVisibility();
                $visibility->setProduct($product);
                $visibility->setWebsite($website);
                $visibility->setVisibility(ProductVisibility::CONFIG);
                $this->getEntityManager()->persist($visibility);
            }
        }
        $this->getEntityManager()->flush();
    }
}
