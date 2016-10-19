<?php

namespace Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;

class ProductVisibilityRepository extends AbstractProductVisibilityRepository
{
    /**
     * Update to 'config' ProductVisibility for products without category with fallback to 'category'.
     *
     * @param InsertFromSelectQueryExecutor $executor
     * @param Scope $scope
     * @param Product|null $product
     */
    public function setToDefaultWithoutCategory(
        InsertFromSelectQueryExecutor $executor,
        Scope $scope,
        Product $product = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select(
                [
                    'product.id',
                    (string)$qb->expr()->literal($scope->getId()),
                    (string)$qb->expr()->literal(ProductVisibility::CONFIG)
                ]
            )
            ->from('OroProductBundle:Product', 'product')
            ->leftJoin(
                'OroCatalogBundle:Category',
                'category',
                Join::WITH,
                $qb->expr()->isMemberOf('product', 'category.products')
            )
            ->leftJoin(
                'OroVisibilityBundle:Visibility\ProductVisibility',
                'productVisibility',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('productVisibility.product', 'product'),
                    $qb->expr()->eq('productVisibility.scope', ':scope')
                )
            )
            ->where($qb->expr()->isNull('productVisibility.id'))
            ->setParameter('scope', $scope)
            ->andWhere($qb->expr()->isNull('category.id'));

        if ($product) {
            $qb->andWhere('product = :product')
                ->setParameter('product', $product);
        }

        $executor->execute(
            'OroVisibilityBundle:Visibility\ProductVisibility',
            ['product', 'scope', 'visibility'],
            $qb
        );
    }
}
