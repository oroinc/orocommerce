<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductPriceRepository extends BasePriceListRepository
{
    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function deleteGeneratedPrices(PriceList $priceList, Product $product = null)
    {
        $qb = $this->getDeleteQbByPriceList($priceList, $product);
        $qb->andWhere($qb->expr()->isNotNull('productPrice.priceRule'))
            ->getQuery()
            ->execute();
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function deleteGeneratedPricesByRule(PriceRule $priceRule, Product $product = null)
    {
        $qb = $this->getDeleteQbByPriceList($priceRule->getPriceList(), $product);
        $qb->andWhere($qb->expr()->eq('productPrice.priceRule', ':priceRule'))
            ->setParameter('priceRule', $priceRule)
            ->getQuery()
            ->execute();
    }

    /**
     * Return product prices for specified price list and product IDs
     *
     * @param int $priceListId
     * @param array $productIds
     * @param bool $getTierPrices
     * @param string|null $currency
     * @param string|null $productUnitCode
     * @param array $orderBy
     *
     * @return ProductPrice[]
     */
    public function findByPriceListIdAndProductIds(
        $priceListId,
        array $productIds,
        $getTierPrices = true,
        $currency = null,
        $productUnitCode = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        if (!$productIds) {
            return [];
        }

        $qb = $this->getFindByPriceListIdAndProductIdsQueryBuilder(
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            $productUnitCode,
            $orderBy
        );
        $qb
            ->addSelect('product', 'unitPrecisions', 'unit')
            ->leftJoin('price.product', 'product')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions')
            ->leftJoin('unitPrecisions.unit', 'unit');

        return $qb->getQuery()->getResult();
    }
}
