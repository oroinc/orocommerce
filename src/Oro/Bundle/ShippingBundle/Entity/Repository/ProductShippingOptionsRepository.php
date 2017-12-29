<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class ProductShippingOptionsRepository extends EntityRepository
{
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
            QueryBuilderUtil::checkIdentifier($productId);
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
