<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents a request to apply a new coupon or remove applied coupon.
 */
final class ChangeCouponRequest
{
    private ?string $couponCode = null;

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): void
    {
        $this->couponCode = $couponCode;
    }
}
