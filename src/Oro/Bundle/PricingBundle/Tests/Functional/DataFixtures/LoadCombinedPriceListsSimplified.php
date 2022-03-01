<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCombinedPriceListsSimplified extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1_2_3',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [
                [
                    'group' => LoadGroups::GROUP1,
                    'website' => LoadWebsiteData::WEBSITE1,
                ],
            ],
            'websites' => [LoadWebsiteData::WEBSITE1],
            'priceListRelations' => [
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_1,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_3,
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => LoadPriceLists::PRICE_LIST_2,
                    'mergeAllowed' => true,
                ]
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadPriceLists::class,
            LoadWebsiteData::class,
            LoadGroups::class
        ];
    }
}
