<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Repository class of ProductShippingOptions entity.
 */
class ProductShippingOptionsRepository extends EntityRepository
{
    public function findIndexedByProductsAndUnits(array $unitsByProductIds): array
    {
        $result = [];
        if (!$unitsByProductIds) {
            return $result;
        }

        foreach ($unitsByProductIds as $productId => $unit) {
            QueryBuilderUtil::checkIdentifier($productId);

            $expressions = [];
            $params = [];
            foreach ($unit as $unitArr) {
                $productParam = 'product_' . $productId;
                $unitParam = QueryBuilderUtil::generateParameterName('unit_');

                $expressions[] = sprintf('(o.product = :%s AND o.productUnit = :%s)', $productParam, $unitParam);
                $params[] = [$productParam => $productId, $unitParam => $unitArr];
            }

            $query = sprintf(
                <<<DQL
                SELECT
                    o.dimensionsHeight,
                    o.dimensionsLength,
                    o.dimensionsWidth,
                    IDENTITY(o.dimensionsUnit) AS dimensionsUnit,
                    IDENTITY(o.weightUnit) AS weightUnit,
                    o.weightValue,
                    u.code
                FROM %s o
                INNER JOIN o.productUnit u INDEX BY u.code
                WHERE %s
            DQL,
                ProductShippingOptions::class,
                implode(' OR ', $expressions)
            );

            $unitsByCode = $this->getEntityManager()
                ->createQuery($query)
                ->execute(array_merge(...$params), AbstractQuery::HYDRATE_ARRAY);

            if ($unitsByCode) {
                $result[$productId] = $unitsByCode;
            }
        }

        return $result;
    }
}
