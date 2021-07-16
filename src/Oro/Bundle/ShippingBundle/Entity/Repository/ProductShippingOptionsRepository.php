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
