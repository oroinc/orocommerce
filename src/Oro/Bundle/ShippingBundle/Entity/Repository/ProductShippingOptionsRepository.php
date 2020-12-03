<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Repository class of ProductShippingOptions entity.
 */
class ProductShippingOptionsRepository extends EntityRepository
{
    /**
     * @param array $unitsByProductIds
     *
     * @return ProductShippingOptions[]
     */
    public function findByProductsAndUnits(array $unitsByProductIds): array
    {
        if (empty($unitsByProductIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('options');

        $expr = $qb->expr();

        $expressions = [];

        foreach ($unitsByProductIds as $productId => $unit) {
            QueryBuilderUtil::checkIdentifier($productId);
            $productIdParamName = 'product_id_'.$productId;

            $productExpr = $expr->eq('options.product', ':'.$productIdParamName);

            $qb->setParameter($productIdParamName, $productId);

            $unitParamName = 'unit_'.$productId;

            $unitExpr = $expr->eq('options.productUnit', ':'.$unitParamName);
            $qb->setParameter($unitParamName, $unit);

            $expressions[] = $expr->andX($productExpr, $unitExpr);
        }

        $qb->andWhere($qb->expr()->orX(...$expressions));

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $unitsByProductIds
     *
     * @return array
     */
    public function findIndexedByProductsAndUnits(array $unitsByProductIds): array
    {
        if (!$unitsByProductIds) {
            return [];
        }

        $expressions = [];
        $params = [];

        foreach ($unitsByProductIds as $productId => $unit) {
            QueryBuilderUtil::checkIdentifier($productId);

            $productParam = 'product_' . $productId;
            $unitParam = 'unit_' . $productId;

            $expressions[] = sprintf('(o.product = :%s AND o.productUnit = :%s)', $productParam, $unitParam);
            $params[] = [$productParam => $productId, $unitParam => $unit];
        }

        $query = sprintf(
            <<<DQL
                SELECT o.dimensionsHeight,
                o.dimensionsLength,
                o.dimensionsWidth,
                IDENTITY(o.dimensionsUnit) AS dimensionsUnit,
                IDENTITY(o.weightUnit) AS weightUnit,
                o.weightValue,
                p.id as productId
                FROM %s o
                INNER JOIN o.product p INDEX BY p.id
                WHERE %s
            DQL,
            ProductShippingOptions::class,
            implode(' OR ', $expressions)
        );

        return $this->getEntityManager()
            ->createQuery($query)
            ->execute(array_merge(...$params), Query::HYDRATE_ARRAY);
    }
}
