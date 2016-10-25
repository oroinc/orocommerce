<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountAddresses as BaseLoadAccountAddresses;

class LoadAccountAddresses extends BaseLoadAccountAddresses
{
    /**
     * @var array
     */
    protected $addresses = [
        [
            'account' => 'sale-account1',
            'label' => 'sale.account.level_1.address_1',
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        [
            'account' => 'sale-account1',
            'label' => 'sale.account.level_1.address_2',
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        [
            'account' => 'sale-account1',
            'label' => 'sale.account.level_1.address_3',
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        [
            'account' => 'sale-account1',
            'label' => 'sale.account.level_1.address_4',
            'street' => '1167 Marion Drive',
            'city' => 'Winter Haven',
            'postalCode' => '33830',
            'country' => 'US',
            'region' => 'FL',
            'primary' => false,
            'types' => [],
            'defaults' => []
        ],
        [
            'account' => 'sale-account1',
            'label' => 'sale.account.level_1.1.address_1',
            'street' => '1215 Caldwell Road',
            'city' => 'ROCHESTER',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadUserData'
        ];
    }
}
