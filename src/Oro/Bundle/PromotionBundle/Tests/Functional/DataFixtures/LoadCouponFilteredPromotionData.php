<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadCouponFilteredPromotionData extends AbstractLoadPromotionData
{
    const PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS = 'promo_corresponding_several_applied_discounts';
    const PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS = 'promo_corresponding_one_applied_discounts';
    const PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS = 'promo_not_corresponding_applied_discounts';
    const PROMO_WITHOUT_DISCOUNTS = 'promo_without_discounts';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            LoadCouponFilterDiscountConfigurationData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPromotions()
    {
        return [
            self::PROMO_CORRESPONDING_SEVERAL_APPLIED_DISCOUNTS => [
                'rule' => [
                    'name' => 'Order percent promotion name',
                    'sortOrder' => 100,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration'
                    => LoadCouponFilterDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_10_PERCENT,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
            self::PROMO_CORRESPONDING_ONE_APPLIED_DISCOUNTS => [
                'rule' => [
                    'name' => 'Order percent promotion name',
                    'sortOrder' => 200,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration'
                    => LoadCouponFilterDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_10_USD,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
            self::PROMO_NOT_CORRESPONDING_APPLIED_DISCOUNTS => [
                'rule' => [
                    'name' => 'Order percent promotion name',
                    'sortOrder' => 300,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration'
                    => LoadCouponFilterDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_20_PERCENT,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
            self::PROMO_WITHOUT_DISCOUNTS => [
                'rule' => [
                    'name' => 'Order percent promotion name',
                    'sortOrder' => 400,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration'
                    => LoadCouponFilterDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_20_USD,
                'useCoupons' => false,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
        ];
    }
}
