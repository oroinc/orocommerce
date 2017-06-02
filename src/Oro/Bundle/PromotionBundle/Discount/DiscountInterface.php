<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Discounts will modify? prices, add discount information, may change structure of line items collection
 *
 * Important!!! Discount services MUST BE registered with shared: false
 */
interface DiscountInterface
{
    //TODO: consider to which type of object discount may be applied
    //TODO: consider how to make discounts usable for subtotals provider

    /**
     * @param array $options
     */
    public function configure(array $options);
}
