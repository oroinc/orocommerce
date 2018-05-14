<?php

namespace Oro\Bundle\PricingBundle\Model\DTO;

/**
 * Used as BC layer and provide possibility to create PriceListTrigger without price list.
 * @deprecated and will be removed in 3.0.
 */
class PriceListProductsTrigger extends PriceListTrigger
{
    /**
     * @param array $products {"<priceListId>" => ["<productId1>", "<productId2>", ...], ...}
     */
    public function __construct(array $products = [])
    {
        $this->products = $products;
    }
}
