<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCombinedPriceLists extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [
                [
                    'group' => 'customer_group.group1',
                    'website' => LoadWebsiteData::WEBSITE1,
                ],
            ],
            'websites' => [LoadWebsiteData::WEBSITE1],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '2t_3f_1t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [
                [
                    'customer' => 'customer.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE1,
                ]
            ],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '2f_1t_3t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [
                [
                    'customer' => 'customer.level_1.2',
                    'website' => LoadWebsiteData::WEBSITE2,
                ]
            ],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => false,
                ],
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '1f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => ['default'],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_1',
                    'mergeAllowed' => false,
                ],
            ],
        ],
        [
            'name' => '2f',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [LoadWebsiteData::WEBSITE2],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => false,
                ],
            ],
        ],
        [
            'name' => '2t_3t',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [
                [
                    'priceList' => 'price_list_2',
                    'mergeAllowed' => true,
                ],
                [
                    'priceList' => 'price_list_3',
                    'mergeAllowed' => true,
                ],
            ],
        ],
        [
            'name' => '1e',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
            'websites' => [],
            'priceListRelations' => [],
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
            LoadCustomers::class,
            LoadGroups::class,
        ];
    }
}
