<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\PromotionBundle\Entity\AppliedCoupon;
use Oro\Bundle\PromotionBundle\Entity\Coupon;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Component\Testing\Unit\EntityTrait;

trait CouponsTrait
{
    use EntityTrait;

    /**
     * @param integer $id
     * @param string $code
     * @param Promotion $promotion
     * @return Coupon
     */
    protected function createCoupon(int $id, string $code, Promotion $promotion)
    {
        /** @var Coupon $coupon */
        $coupon = $this->getEntity(
            Coupon::class,
            [
                'id' => $id,
                'code' => $code,
                'enabled' => true,
                'promotion' => $promotion,
                'usesPerPerson' => null,
                'usesPerCoupon' => null,
            ]
        );

        return $coupon;
    }

    /**
     * @param integer $id
     * @param string $code
     * @param integer $promotionId
     * @return AppliedCoupon
     */
    protected function createAppliedCoupon(int $id, string $code, int $promotionId)
    {
        $appliedCoupon = new AppliedCoupon();

        return $appliedCoupon
            ->setSourceCouponId($id)
            ->setCouponCode($code)
            ->setSourcePromotionId($promotionId);
    }
}
