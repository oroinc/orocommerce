<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;

/**
 * Loads discount configurations for multiple promotions sort order testing.
 */
class LoadMultiplePromotionsDiscountConfigurationData extends AbstractLoadDiscountConfigurationData
{
    #[\Override]
    public function getDiscountConfiguration(): array
    {
        return [
            LoadMultiplePromotionsWithSortOrderData::DISCOUNT_CONFIGURATION_1 => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 1,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
            LoadMultiplePromotionsWithSortOrderData::DISCOUNT_CONFIGURATION_2 => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 1,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
            LoadMultiplePromotionsWithSortOrderData::DISCOUNT_CONFIGURATION_3 => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 1,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
            LoadMultiplePromotionsWithSortOrderData::DISCOUNT_CONFIGURATION_4 => [
                'type' => 'order',
                'options' => [
                    AbstractDiscount::DISCOUNT_TYPE => DiscountInterface::TYPE_AMOUNT,
                    AbstractDiscount::DISCOUNT_VALUE => 1,
                    AbstractDiscount::DISCOUNT_CURRENCY => 'USD',
                ],
            ],
        ];
    }
}
