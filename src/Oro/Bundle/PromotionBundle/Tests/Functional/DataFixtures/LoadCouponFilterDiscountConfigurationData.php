<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;

class LoadCouponFilterDiscountConfigurationData extends AbstractLoadDiscountConfigurationData
{
    const DISCOUNT_CONFIGURATION_ORDER_10_PERCENT = 'discount_configuration_order_10_percent';
    const DISCOUNT_CONFIGURATION_ORDER_10_USD = 'discount_configuration_order_10_usd';
    const DISCOUNT_CONFIGURATION_ORDER_20_PERCENT = 'DISCOUNT_CONFIGURATION_ORDER_20_PERCENT';
    const DISCOUNT_CONFIGURATION_ORDER_20_USD = 'DISCOUNT_CONFIGURATION_ORDER_20_USD';

    /**
     * {@inheritdoc}
     */
    public function getDiscountConfiguration()
    {
        return [
            self::DISCOUNT_CONFIGURATION_ORDER_10_PERCENT => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.1,
                ],
            ],
            self::DISCOUNT_CONFIGURATION_ORDER_10_USD => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
            self::DISCOUNT_CONFIGURATION_ORDER_20_PERCENT => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.2,
                ],
            ],
            self::DISCOUNT_CONFIGURATION_ORDER_20_USD => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 20,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
        ];
    }
}
