<?php

namespace Oro\Bundle\PromotionBundle\Discount;

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

    /**
     * @return DiscountInterface
     */
    public function getDiscount(): DiscountInterface
    {
        return $this->discount;
    }

    /**
     * @return float
     */
    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }
}
