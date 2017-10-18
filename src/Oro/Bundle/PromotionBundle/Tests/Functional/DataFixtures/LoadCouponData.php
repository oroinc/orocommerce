<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadCouponData extends AbstractLoadCouponData
{
    const COUPON_WITHOUT_PROMO_AND_VALID_UNTIL = 'coupon_without_promo_and_valid_until';
    const COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL = 'coupon_with_promo_and_without_valid_until';
    const COUPON_WITH_PROMO_AND_EXPIRED = 'coupon_with_promo_and_expired';
    const COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL = 'coupon_with_promo_and_valid_from_and_until';
    const COUPON_WITH_PROMO_AND_NOT_STARTED = 'coupon_with_promo_and_not_started';
    const COUPON_DISABLED = 'coupon_disabled';
    const COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL = 'coupon_with_shipping_promo_and_valid_until';

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
                'usesPerPerson' => 1,
            ],
            self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL => [
                'code' => self::COUPON_WITH_PROMO_AND_WITHOUT_VALID_UNTIL,
                'usesPerCoupon' => 2,
                'usesPerPerson' => 2,
                'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
            ],
            self::COUPON_WITH_PROMO_AND_EXPIRED => [
                'code' => self::COUPON_WITH_PROMO_AND_EXPIRED,
                'usesPerCoupon' => 3,
                'usesPerPerson' => 3,
                'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
                'validUntil' => '-1 day',
            ],
            self::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL => [
                'code' => self::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL,
                'usesPerCoupon' => 4,
                'usesPerPerson' => 4,
                'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
                'validFrom' => '-1 day',
                'validUntil' => '+1 day',
            ],
            self::COUPON_DISABLED => [
                'code' => self::COUPON_DISABLED,
                'enabled' => false,
                'usesPerCoupon' => 4,
                'usesPerPerson' => 4,
                'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
                'validUntil' => '+1 day',
            ],
            self::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL => [
                'code' => self::COUPON_WITH_SHIPPING_PROMO_AND_VALID_UNTIL,
                'usesPerCoupon' => 5,
                'usesPerPerson' => 5,
                'promotion' => LoadPromotionData::SHIPPING_PROMOTION,
                'validUntil' => '+1 day',
            ],
            self::COUPON_WITH_PROMO_AND_NOT_STARTED => [
                'code' => self::COUPON_WITH_PROMO_AND_NOT_STARTED,
                'usesPerCoupon' => 5,
                'usesPerPerson' => 5,
                'promotion' => LoadPromotionData::ORDER_PERCENT_PROMOTION,
                'validFrom' => '+1 day',
            ],
        ];
    }
}
