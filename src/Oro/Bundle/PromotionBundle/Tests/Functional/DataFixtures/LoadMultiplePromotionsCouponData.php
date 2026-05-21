<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * Loads coupons for multiple promotions sort order testing.
 */
class LoadMultiplePromotionsCouponData extends AbstractLoadCouponData
{
    public const string COUPON_FOR_PROMOTION_10 = 'test-1';
    public const string COUPON_FOR_PROMOTION_NEGATIVE_10 = 'test-2';
    public const string COUPON_FOR_PROMOTION_20 = 'test-3';
    public const string COUPON_FOR_PROMOTION_5 = 'test-4';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadMultiplePromotionsWithSortOrderData::class,
            LoadOrganization::class,
        ];
    }

    #[\Override]
    protected function getCoupons(): array
    {
        return [
            self::COUPON_FOR_PROMOTION_10 => [
                'code' => self::COUPON_FOR_PROMOTION_10,
                'usesPerCoupon' => 100,
                'usesPerPerson' => 100,
                'promotion' => LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_10,
            ],
            self::COUPON_FOR_PROMOTION_NEGATIVE_10 => [
                'code' => self::COUPON_FOR_PROMOTION_NEGATIVE_10,
                'usesPerCoupon' => 100,
                'usesPerPerson' => 100,
                'promotion' => LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_NEGATIVE_10,
            ],
            self::COUPON_FOR_PROMOTION_20 => [
                'code' => self::COUPON_FOR_PROMOTION_20,
                'usesPerCoupon' => 100,
                'usesPerPerson' => 100,
                'promotion' => LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_20,
            ],
            self::COUPON_FOR_PROMOTION_5 => [
                'code' => self::COUPON_FOR_PROMOTION_5,
                'usesPerCoupon' => 100,
                'usesPerPerson' => 100,
                'promotion' => LoadMultiplePromotionsWithSortOrderData::PROMOTION_SORT_ORDER_5,
            ],
        ];
    }
}
