<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\AbstractLoadCouponData;

class LoadAdditionalCouponData extends AbstractLoadCouponData
{
    public const ADDITIONAL_COUPON_WITH_PROMO = 'additional_coupon_with_promo';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadAdditionalPromotionData::class];
    }

    #[\Override]
    protected function getCoupons(): array
    {
        return [
            self::ADDITIONAL_COUPON_WITH_PROMO => [
                'code' => self::ADDITIONAL_COUPON_WITH_PROMO,
                'usesPerCoupon' => 1,
                'usesPerPerson' => 1,
                'promotion' => LoadAdditionalPromotionData::ORDER_PERCENT_ADDITIONAL_PROMOTION,
                'validFrom' => '-1 day',
                'validUntil' => '+1 day'
            ]
        ];
    }
}
