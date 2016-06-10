<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class PriceAttributeProductPriceRepository extends EntityRepository
{
    /**
     * Return product prices for specified price list and product IDs
     *
     * @param integer[] $priceAttributePriceListIds
     * @param array $productIds
     * @param string|null $currency
     * @param string|null $productUnitCode
     * @param array $orderBy
     *
     * @return ProductPrice[]
     */
    public function findByPriceAttributeProductPriceIdsIdsAndProductIds(
        $priceAttributePriceListIds,
        array $productIds,
        $currency = null,
        $productUnitCode = null,
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

        if ($currency) {
            $qb
                ->andWhere($qb->expr()->eq('price.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        if ($productUnitCode) {
            $qb
                ->andWhere($qb->expr()->eq('IDENTITY(price.unit)', ':productUnitCode'))
                ->setParameter('productUnitCode', $productUnitCode);
        }

        foreach ($orderBy as $fieldName => $orderDirection) {
            $qb->addOrderBy('price.' . $fieldName, $orderDirection);
        }

        return $qb->getQuery()->getResult();
    }
}
