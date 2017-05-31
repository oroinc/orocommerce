<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class PriceAttributeProductPriceRepository extends BaseProductPriceRepository
{
    /**
     * Return product prices for specified price list and product IDs
     *
     * @param integer[] $priceAttributePriceListIds
     * @param array $productIds
     * @param array $orderBy
     *
     * @return ProductPrice[]
     */
    public function findByPriceAttributeProductPriceIdsAndProductIds(
        $priceAttributePriceListIds,
        array $productIds,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        if (!$productIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('price');
        $qb
            ->where(
                $qb->expr()->in('IDENTITY(price.priceList)', ':priceListIds'),
                $qb->expr()->in('IDENTITY(price.product)', ':productIds')
            )
            ->setParameter('priceListIds', $priceAttributePriceListIds)
            ->setParameter('productIds', $productIds);

        foreach ($orderBy as $fieldName => $orderDirection) {
            $qb->addOrderBy('price.' . $fieldName, $orderDirection);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return mixed
     */
    public function removeByUnitProduct(Product $product, ProductUnit $unit)
    {
        $qb = $this->createQueryBuilder('productPrice');

        $qb->delete()
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('productPrice.unit', ':unit'),
                    $qb->expr()->eq('productPrice.product', ':product')
                )
            )
            ->setParameters([
                'unit' => $unit,
                'product' => $product
            ]);

        return $qb->getQuery()->execute();
    }
}
