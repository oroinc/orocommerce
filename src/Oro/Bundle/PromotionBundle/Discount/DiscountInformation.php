<?php

namespace Oro\Bundle\PromotionBundle\Discount;

/**
 * Encapsulates discount information including the discount object and calculated amount.
 *
 * Holds a reference to a discount and its calculated amount for a specific entity,
 * used to track and communicate discount details throughout the promotion system.
 */
class DiscountInformation
{
    /**
     * @var DiscountInterface
     */
    protected $discount;

    /**
     * @var float
     */
    protected $discountAmount;

    /**
     * @param DiscountInterface $discount
     * @param float $amount
     */
    public function __construct(DiscountInterface $discount, $amount)
    {
        $this->discount = $discount;
        $this->discountAmount = $amount;
    }

    public function getDiscount(): DiscountInterface
    {
        return $this->discount;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }
}
