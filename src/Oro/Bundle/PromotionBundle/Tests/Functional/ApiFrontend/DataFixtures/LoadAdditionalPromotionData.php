<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\AbstractLoadPromotionData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadSegmentData;

class LoadAdditionalPromotionData extends AbstractLoadPromotionData
{
    public const ORDER_PERCENT_ADDITIONAL_PROMOTION = 'order_percent_additional_promotion';

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            [LoadSegmentData::class, LoadAdditionalDiscountConfigurationData::class],
            parent::getDependencies()
        );
    }

    #[\Override]
    protected function getPromotions(): array
    {
        return [
            self::ORDER_PERCENT_ADDITIONAL_PROMOTION => [
                'rule' => [
                    'name' => 'Order percent additional promotion name',
                    'sortOrder' => 100,
                    'enabled' => true,
                ],
                'segmentReference' => LoadSegmentData::PRODUCT_DYNAMIC_SEGMENT,
                'discountConfiguration' =>
                    LoadAdditionalDiscountConfigurationData::ADDITIONAL_DISCOUNT_CONFIGURATION_ORDER_PERCENT,
                'useCoupons' => true,
                'scopeCriterias' => [
                    [
                        'website' => null,
                        'customerGroup' => null,
                        'customer' => null
                    ]
                ]
            ]
        ];
    }
}
