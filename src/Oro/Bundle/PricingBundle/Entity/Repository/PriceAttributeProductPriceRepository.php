<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceAttributeProductPriceRepository extends BaseProductPriceRepository
{
    /**
     * @param BasePriceList $priceList
     *
     * @return int
     */
    public function deletePricesByPriceList(BasePriceList $priceList): int
    {
        $qb = $this->createQueryBuilder('price');

        return $qb->delete()
            ->where($qb->expr()->eq('price.priceList', ':priceList'))
            ->setParameter('priceList', $priceList)
            ->getQuery()
            ->execute();
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param integer[] $priceAttributePriceListIds
     * @param array     $productIds
     * @param array     $orderBy
     *
     * @return PriceAttributeProductPrice[]
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
     * {@inheritDoc}
     */
    protected function getPriceListIdsByProduct(Product $product)
    {
        $qb = $this->createQueryBuilder('productToPriceList');

        $result = $qb->select('DISTINCT IDENTITY(productToPriceList.priceList) as priceListId')
            ->where('productToPriceList.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }
}
