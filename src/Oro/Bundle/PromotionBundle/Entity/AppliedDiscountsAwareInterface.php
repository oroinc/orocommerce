<?php

namespace Oro\Bundle\PromotionBundle\Entity;

interface AppliedDiscountsAwareInterface
{
    /**
     * @param AppliedDiscount $discount
     */
    public function addAppliedDiscount($discount);

    /**
     * @return AppliedDiscount[]
     */
    public function getAppliedDiscounts();

    /**
     * @param AppliedDiscount $discount
     */
    public function removeAppliedDiscount($discount);

    /**
     * @param AppliedDiscount[] $discounts
     * @return $this
     */
    public function setAppliedDiscounts($discounts);
}
