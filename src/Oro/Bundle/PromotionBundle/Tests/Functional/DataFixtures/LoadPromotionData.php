<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadPromotionData extends AbstractLoadPromotionData
{
    const ORDER_PERCENT_PROMOTION = 'order_percent_promotion';
    const ORDER_AMOUNT_PROMOTION = 'order_amount_promotion';
    const SHIPPING_PROMOTION = 'shipping_promotion';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentData::class,
            LoadDiscountConfigurationData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPromotions()
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
                    'name' => 'Order percent promotion name',
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
