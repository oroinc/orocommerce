<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;

class LoadDiscountConfigurationData extends AbstractLoadDiscountConfigurationData
{
    const DISCOUNT_CONFIGURATION_ORDER_PERCENT = 'discount_configuration_order_percent';
    const DISCOUNT_CONFIGURATION_ORDER_AMOUNT = 'discount_configuration_order_amount';

    /**
     * {@inheritdoc}
     */
    public function getDiscountConfiguration()
    {
        return [
            self::DISCOUNT_CONFIGURATION_ORDER_PERCENT => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.1,
                ],
            ],
            self::DISCOUNT_CONFIGURATION_ORDER_AMOUNT => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 10,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
        ];
    }
}
