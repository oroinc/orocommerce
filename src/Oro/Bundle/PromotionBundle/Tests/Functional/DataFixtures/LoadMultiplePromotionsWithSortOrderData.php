<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

/**
 * Loads promotions with different sort orders for testing coupon ordering.
 */
class LoadMultiplePromotionsWithSortOrderData extends AbstractLoadPromotionData
{
    public const string PROMOTION_SORT_ORDER_NEGATIVE_10 = 'promotion_sort_order_negative_10';
    public const string PROMOTION_SORT_ORDER_5 = 'promotion_sort_order_5';
    public const string PROMOTION_SORT_ORDER_10 = 'promotion_sort_order_10';
    public const string PROMOTION_SORT_ORDER_20 = 'promotion_sort_order_20';

    public const string DISCOUNT_CONFIGURATION_1 = 'discount_config_sort_order_1';
    public const string DISCOUNT_CONFIGURATION_2 = 'discount_config_sort_order_2';
    public const string DISCOUNT_CONFIGURATION_3 = 'discount_config_sort_order_3';
    public const string DISCOUNT_CONFIGURATION_4 = 'discount_config_sort_order_4';

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            [
                LoadSegmentData::class,
                LoadMultiplePromotionsDiscountConfigurationData::class,
            ],
            parent::getDependencies()
        );
    }

    #[\Override]
    protected function getPromotions(): array
    {
        return [
            self::PROMOTION_SORT_ORDER_10 => [
                'rule' => [
                    'name' => 'Promotion 10',
                    'sortOrder' => 10,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => self::DISCOUNT_CONFIGURATION_1,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null,
                    ],
                ],
            ],
            self::PROMOTION_SORT_ORDER_NEGATIVE_10 => [
                'rule' => [
                    'name' => 'Promotion -10',
                    'sortOrder' => -10,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => self::DISCOUNT_CONFIGURATION_2,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null,
                    ],
                ],
            ],
            self::PROMOTION_SORT_ORDER_20 => [
                'rule' => [
                    'name' => 'Promotion 20',
                    'sortOrder' => 20,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => self::DISCOUNT_CONFIGURATION_3,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null,
                    ],
                ],
            ],
            self::PROMOTION_SORT_ORDER_5 => [
                'rule' => [
                    'name' => 'Promotion 5',
                    'sortOrder' => 5,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' => self::DISCOUNT_CONFIGURATION_4,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null,
                    ],
                ],
            ],
        ];
    }
}
