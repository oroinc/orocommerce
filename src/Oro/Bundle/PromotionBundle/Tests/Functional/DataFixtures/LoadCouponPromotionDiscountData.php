<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadCouponPromotionDiscountData extends AbstractLoadCouponData
{
    const COUPON_ORDER = 'coupon_order';
    const COUPON_SHIPPING = 'coupon_shipping';
    const COUPON_WITH_NOT_APPLICABLE_PROMOTION = 'coupon_with_not_applicable_promotion';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPromotionDiscountData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCoupons()
    {
        return [
            self::COUPON_SHIPPING => [
                'code' => self::COUPON_SHIPPING,
                'usesPerCoupon' => 1,
                'usesPerPerson' => 1,
                'promotion' => 'promo_shipping_20%_flat_rate_method_with_coupon'
            ],
            self::COUPON_ORDER => [
                'code' => self::COUPON_ORDER,
                'usesPerCoupon' => 2,
                'usesPerPerson' => 2,
                'promotion' => 'promo_order_10_USD_with_coupon'
            ],
            self::COUPON_WITH_NOT_APPLICABLE_PROMOTION => [
                'code' => self::COUPON_WITH_NOT_APPLICABLE_PROMOTION,
                'usesPerCoupon' => 3,
                'usesPerPerson' => 3,
                'promotion' => 'promo_shipping_10_USD_unsupported_method_with_coupon'
            ]
        ];
    }
}
