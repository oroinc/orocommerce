<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserAddresses as BaseLLoadAccountUserAddresses;

class LoadAccountUserAddresses extends BaseLLoadAccountUserAddresses
{
    /**
     * @var array
     */
    protected $addresses = [
        [
            'account_user' => 'sale-account1-user1@example.com',
            'label' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_1',
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        [
            'account_user' => 'sale-account1-user1@example.com',
            'label' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_2',
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        [
            'account_user' => 'sale-account1-user1@example.com',
            'label' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_3',
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        [
            'account_user' => 'sale-account1-user1@example.com',
            'label' => 'sale.grzegorz.brzeczyszczykiewicz@example.com.address_4',
            'street' => '1167 Marion Drive',
            'city' => 'Winter Haven',
            'postalCode' => '33830',
            'country' => 'US',
            'region' => 'FL',
            'primary' => false,
            'types' => ['shipping' => true],
            'defaults' => []
        ],
        [
            'account_user' => 'sale-account1-user1@example.com',
            'label' => 'sale.other.user@test.com.address_1',
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
