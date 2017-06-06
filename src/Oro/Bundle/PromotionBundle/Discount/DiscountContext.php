<?php

namespace Oro\Bundle\PromotionBundle\Discount;

class DiscountContext
{
    /**
     * @var DiscountLineItem[]
     */
    protected $lineItems;

    /**
     * @var array|DiscountInformation[]
     */
    protected $totalDiscounts = [];

    /**
     * @var DiscountInformation
     */
    protected $appliedTotalDiscount;

    /**
     * @var array|DiscountInformation[]
     */
    protected $shippingDiscounts = [];

    /**
     * @var DiscountInformation
     */
    protected $appliedShippingDiscount;
}
