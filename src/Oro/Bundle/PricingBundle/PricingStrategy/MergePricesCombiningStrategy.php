<?php

namespace Oro\Bundle\PricingBundle\PricingStrategy;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Implements combining price strategy base on PriceList priority and additional flag "mergeAllowed"
 */
class MergePricesCombiningStrategy extends AbstractPriceCombiningStrategy
{
    const NAME = 'merge_by_priority';

    /**
     * {@inheritdoc}
     */
    protected function processRelation(
        CombinedPriceList $combinedPriceList,
        CombinedPriceListToPriceList $priceListRelation,
        Product $product = null
    ) {
        $this->getCombinedProductPriceRepository()->insertPricesByPriceList(
            $this->insertFromSelectQueryExecutor,
            $combinedPriceList,
            $priceListRelation->getPriceList(),
            $priceListRelation->isMergeAllowed(),
            $product
        );
    }
}
