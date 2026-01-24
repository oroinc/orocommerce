<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Marks discounts that are aware of product unit codes.
 *
 * Provides a constant for accessing the product unit code attribute
 * used by discounts that apply to specific product units.
 */
interface DiscountProductUnitCodeAwareInterface
{
    const DISCOUNT_PRODUCT_UNIT_CODE = 'discount_product_unit_code';
}
