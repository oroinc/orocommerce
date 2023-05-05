<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;

class LoadCombinedPriceListForDefaultWebsite extends AbstractCombinedPriceListsFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'default',
            'enabled' => true,
            'calculated' => false,
            'priceListsToCustomers' => [],
            'websites' => [LoadWebsite::WEBSITE],
            'priceListsToCustomerGroups' => [],
            'priceListRelations' => []
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsite::class
        ];
    }
}
