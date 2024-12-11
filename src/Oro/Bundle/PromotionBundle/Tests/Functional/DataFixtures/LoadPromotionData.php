<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadPromotionData extends AbstractLoadPromotionData
{
    public const ORDER_PERCENT_PROMOTION = 'order_percent_promotion';
    public const ORDER_AMOUNT_PROMOTION = 'order_amount_promotion';
    public const SHIPPING_PROMOTION = 'shipping_promotion';

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            [LoadSegmentData::class, LoadDiscountConfigurationData::class],
            parent::getDependencies()
        );
    }

    #[\Override]
    protected function getPromotions(): array
    {
        return [
            self::ORDER_PERCENT_PROMOTION => [
                'rule' => [
                    'name' => 'Order percent promotion name',
                    'sortOrder' => 100,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => LoadDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_PERCENT,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
            self::ORDER_AMOUNT_PROMOTION => [
                'rule' => [
                    'name' => 'Order amount promotion name',
                    'sortOrder' => 200,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => LoadDiscountConfigurationData::DISCOUNT_CONFIGURATION_ORDER_AMOUNT,
                'useCoupons' => false,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ],
            self::SHIPPING_PROMOTION => [
                'rule' => [
                    'name' => 'Shipping promotion name',
                    'sortOrder' => 200,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => LoadDiscountConfigurationData::DISCOUNT_CONFIGURATION_SHIPPING_AMOUNT,
                'useCoupons' => true,
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
