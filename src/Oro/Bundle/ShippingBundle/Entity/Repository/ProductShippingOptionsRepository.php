<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

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
                WHERE o.product = :productId AND o.productUnit IN (:productUnits)
            DQL,
            ProductShippingOptions::class
        );

        $unitsByCodeQuery = $this->getEntityManager()->createQuery($query);

        foreach ($unitsByProductIds as $productId => $unitCodes) {
            $shippingOptionsByCode = $unitsByCodeQuery->execute(
                ['productId' => $productId, 'productUnits' => $unitCodes],
                AbstractQuery::HYDRATE_ARRAY
            );

            if ($shippingOptionsByCode) {
                $result[$productId] = $shippingOptionsByCode;
            }
        }

        return $result;
    }
}
