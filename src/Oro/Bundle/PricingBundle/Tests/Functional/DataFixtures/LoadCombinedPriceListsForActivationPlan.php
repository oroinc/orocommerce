<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadCombinedPriceListsForActivationPlan extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => '1t_2t_3t',
            'enabled' => true,
            'calculated' => true,
            'priceListsToCustomers' => [],
            'priceListsToCustomerGroups' => [],
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
