<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

class ProductShippingOptionsRepository extends EntityRepository
{
    /**
     * @param Product[]     $products
     * @param ProductUnit[] $productUnits
     *
     * @return ProductShippingOptions[]
     */
    public function findByProductsAndProductUnits(array $products, array $productUnits): array
    {
        return $this->findBy(
            [
                'product' => $products,
                'productUnit' => $productUnits,
            ]
        );
    }

    /**
     * @param array $unitsByProductIds
     *
     * @return ProductShippingOptions[]
     */
    public function findByProductsAndUnits(array $unitsByProductIds): array
    {
        if (count($unitsByProductIds) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('options');
        $qb
            ->join('options.product', 'product');

        $expr = $qb->expr();

        $expressions = [];

        foreach ($unitsByProductIds as $productId => $unit) {
            $productIdParamName = 'product_id_'.$productId;

            $productExpr = $expr->eq('product.id', ':'.$productIdParamName);

            $qb->setParameter($productIdParamName, $productId);

            $unitParamName = 'unit_'.$productId;

            $unitExpr = $expr->eq('options.productUnit', ':'.$unitParamName);
            $qb->setParameter($unitParamName, $unit);

            $expressions[] = $expr->andX($productExpr, $unitExpr);
        }

        $qb->andWhere($qb->expr()->orX(...$expressions));

        return $qb->getQuery()->getResult();
    }
}
