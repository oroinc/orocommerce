<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

class LoadPromotionData extends AbstractLoadPromotionData
{
    public const ORDER_PERCENT_PROMOTION = 'order_percent_promotion';
    public const ORDER_PERCENT_PROMOTION_IN_FUTURE = 'order_percent_promotion_in_future';
    public const ORDER_AMOUNT_PROMOTION = 'order_amount_promotion';
    public const SHIPPING_PROMOTION = 'shipping_promotion';

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return array_merge(
            [LoadSegmentData::class, LoadDiscountConfigurationData::class],
            parent::getDependencies()
        );
    }

    /**
     * {@inheritDoc}
     */
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
            self::ORDER_PERCENT_PROMOTION_IN_FUTURE => [
                'rule' => [
                    'name' => 'Order percent promotion name in future',
                    'sortOrder' => 100,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => LoadDiscountConfigurationData::ANOTHER_DISCOUNT_CONFIGURATION_ORDER_PERCENT,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ],
                'schedules' => [
                    [
                        'activateAt' => new \DateTime('now +1 day'),
                        'deactivateAt' => new \DateTime('now +2 day'),
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
