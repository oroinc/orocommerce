<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadPriceListFallbackSettingsForCPLBuilderFacade extends LoadPriceListFallbackSettings
{
    /**
     * @var array
     */
    protected $fallbackSettings = [
        'customer' => [
            LoadWebsiteData::WEBSITE1 => [
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_2,
                    'customer' => 'customer.level_1.3',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_3,
                    'customer' => 'customer.level_1.2.1',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_1,
                    'customer' => 'customer.level_1_1',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => 'US_customer_1.1.1_price_list_fallback',
                    'customer' => 'customer.level_1.1.1',
                    'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP,
                ],
            ],
            LoadWebsiteData::WEBSITE2 => [
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_5,
                    'customer' => 'customer.level_1.3',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_6,
                    'customer' => 'customer.level_1.2.1',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => self::WEBSITE_CUSTOMER_FALLBACK_4,
                    'customer' => 'customer.level_1_1',
                    'fallback' => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY,
                ],
                [
                    'reference' => 'Canada_customer_1.1.1_price_list_fallback',
                    'customer' => 'customer.level_1.1.1',
                    'fallback' => PriceListCustomerFallback::ACCOUNT_GROUP,
                ],
            ],
        ],
        'customerGroup' => [
            LoadWebsiteData::WEBSITE1 => [
                [
                    'reference' => self::WEBSITE_CUSTOMER_GROUP_FALLBACK_2,
                    'group' => 'customer_group.group2',
                    'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
                ]
            ],
            LoadWebsiteData::WEBSITE2 => [
                [
                    'reference' => self::WEBSITE_CUSTOMER_GROUP_FALLBACK_4,
                    'group' => 'customer_group.group2',
                    'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
                ],
            ],
        ],
        'website' => [
            LoadWebsiteData::WEBSITE2 => [
                'reference' => self::WEBSITE_FALLBACK_2,
                'fallback' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
            ],
        ],
    ];
}
