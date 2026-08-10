<?php

namespace Oro\Bundle\ShippingBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;

/**
 * Repository class of ProductShippingOptions entity.
 */
class ProductShippingOptionsRepository extends ServiceEntityRepository
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
                    IDENTITY(o.productUnit) AS code,
                    IDENTITY(o.product) AS product
                FROM %s o
                WHERE o.product IN (:productIds)
            DQL,
            ProductShippingOptions::class
        );

        $unitsByCodeQuery = $this->getEntityManager()->createQuery($query);
        $productIds = array_keys($unitsByProductIds);
        $shippingOptionsData = $unitsByCodeQuery->execute(
            ['productIds' => $productIds],
            AbstractQuery::HYDRATE_ARRAY
        );

        foreach ($shippingOptionsData as $shippingOptionsDatum) {
            $productId = $shippingOptionsDatum['product'];
            $unitCode = $shippingOptionsDatum['code'];
            if (!isset($unitsByProductIds[$productId][$unitCode])) {
                continue;
            }

            $result[$productId][$unitCode] = $shippingOptionsDatum;
        }

        return $result;
    }
}
