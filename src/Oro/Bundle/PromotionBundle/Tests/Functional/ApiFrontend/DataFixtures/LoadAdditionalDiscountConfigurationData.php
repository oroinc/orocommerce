<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\AbstractLoadDiscountConfigurationData;

class LoadAdditionalDiscountConfigurationData extends AbstractLoadDiscountConfigurationData
{
    public const ADDITIONAL_DISCOUNT_CONFIGURATION_ORDER_PERCENT = 'additional_discount_configuration_order_percent';

    #[\Override]
    public function getDiscountConfiguration()
    {
        return [
            self::ADDITIONAL_DISCOUNT_CONFIGURATION_ORDER_PERCENT => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => AbstractDiscount::TYPE_PERCENT,
                    AbstractDiscount::DISCOUNT_VALUE => 0.05
                ]
            ]
        ];
    }
}
