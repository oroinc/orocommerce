<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
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
}
