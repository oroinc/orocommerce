<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadCouponFilterCouponData extends AbstractLoadCouponData
{
    const COUPON1 = 'COUPON_1';
    const COUPON2 = 'COUPON_2';
    const COUPON3 = 'COUPON_3';
    const COUPON4 = 'COUPON_4';
    const COUPON5 = 'COUPON_5';
    const COUPON6 = 'COUPON_6';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCouponFilteredPromotionData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCoupons()
    {
        return  [
            self::COUPON1 => [
                'code' => self::COUPON1,
                'usesPerCoupon' => 1,
                'usesPerPerson' => 1,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS
            ],
            self::COUPON2 => [
                'code' => self::COUPON2,
                'usesPerCoupon' => 1,
                'usesPerPerson' => 1,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS,
            ],
            self::COUPON3 => [
                'code' => self::COUPON3,
                'usesPerCoupon' => 1,
                'usesPerPerson' => 1,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS,
            ],
            self::COUPON4 => [
                'code' => self::COUPON4,
                'usesPerCoupon' => 3,
                'usesPerPerson' => 2,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS,
            ],
            self::COUPON5 => [
                'code' => self::COUPON5,
                'usesPerCoupon' => 3,
                'usesPerPerson' => 2,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS,
            ],
            self::COUPON5 => [
                'code' => self::COUPON6,
                'usesPerCoupon' => 3,
                'usesPerPerson' => 2,
                'promotion' => LoadCouponFilteredPromotionData::PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS,
            ],
        ];
    }
}
