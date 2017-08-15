<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadCouponData extends AbstractLoadCouponData
{
    const COUPON_WITHOUT_PROMO_AND_VALID_UNTIL = 'coupon_without_promo_and_valid_until';
    const COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL = 'coupon_with_promo_and_without_valid_until';
    const COUPON_WITH_PROMO_AND_EXPIRED = 'coupon_with_promo_and_expired';
    const COUPON_WITH_PROMO_AND_VALID_UNTIL = 'coupon_with_promo_and_valid_until';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPromotionData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCoupons()
    {
        return [
            self::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL => [
                'code' => self::COUPON_WITHOUT_PROMO_AND_VALID_UNTIL,
                'usesPerCoupon' => 1,
                'usesPerUser' => 1,
            ],
            self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL => [
                'code' => self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
                'usesPerCoupon' => 1,
                'usesPerUser' => 1,
                'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
            ],
            self::COUPON_WITH_PROMO_AND_EXPIRED => [
                'code' => self::COUPON_WITH_PROMO_AND_EXPIRED,
                'usesPerCoupon' => 1,
                'usesPerUser' => 1,
                'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
                'validUntil' => '-1 day',
            ],
            self::COUPON_WITH_PROMO_AND_VALID_UNTIL => [
                'code' => self::COUPON_WITH_PROMO_AND_VALID_UNTIL,
                'usesPerCoupon' => 3,
                'usesPerUser' => 2,
                'promotion' => LoadPromotionData::ORDER_AMOUNT_PROMOTION,
                'validUntil' => '+1 day',
            ],
        ];
    }
}
