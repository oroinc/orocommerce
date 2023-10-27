<?php

namespace Oro\Bundle\PricingBundle\Datagrid;

use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;

/**
 * Adds CPL price and price per unit columns, sorters, filters for each currency enabled in current price list.
 */
class CombinedProductPriceDatagridExtension extends ProductPriceDatagridExtension
{
    protected function getFilterType(): string
    {
        return 'combined-product-price';
    }

    protected function getPriceClassName(): string
    {
        return CombinedProductPrice::class;
    }
}
