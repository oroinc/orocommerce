<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;

class LoadAccountUserAddressesACLData extends AbstractAddressesFixture implements DependentFixtureInterface
{
    const ADDRESS_ACC_1_USER_LOCAL = 'address_account1_user_local';
    const ADDRESS_ACC_1_USER_DEEP = 'address_account1_user_deep';
    const ADDRESS_ACC_1_USER_BASIC = 'address_account1_user_basic';
    const ADDRESS_ACC_1_1_USER_LOCAL = 'address_account1.1_user_local';
    const ADDRESS_ACC_2_USER_LOCAL = 'address_account2_user_local';

    /**
     * @var array
     */
    protected $addresses = [
        [
            'account_user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_LOCAL,
            'label' => self::ADDRESS_ACC_1_USER_LOCAL,
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        [
            'account_user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_DEEP,
            'label' => self::ADDRESS_ACC_1_USER_DEEP,
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        [
            'account_user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_ROLE_BASIC,
            'label' => self::ADDRESS_ACC_1_USER_BASIC,
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        [
            'account_user' => LoadAccountUserAddressACLData::USER_ACCOUNT_1_1_ROLE_LOCAL,
            'label' => self::ADDRESS_ACC_1_1_USER_LOCAL,
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
            'account_user' => LoadAccountUserAddressACLData::USER_ACCOUNT_2_ROLE_LOCAL,
            'label' => self::ADDRESS_ACC_2_USER_LOCAL,
            'street' => '2849 Junkins Avenue',
            'city' => 'Albany',
            'postalCode' => '31707',
            'country' => 'US',
            'region' => 'GA',
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
            LoadAccountUserAddressACLData::class
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $addressData) {
            $address = new AccountUserAddress();
            $address->setSystemOrganization($this->getOrganization($manager));
            $address->setFrontendOwner($this->getReference($addressData['account_user']));
            $this->addAddress($manager, $addressData, $address);
        }

        $manager->flush();
    }
}
