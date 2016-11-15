<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\CustomerBundle\Entity\AccountAddress;

class LoadAccountAddressesACLData extends AbstractAddressesFixture implements DependentFixtureInterface
{
    const ADDRESS_ACC_1_USER_LOCAL = 'address_account1_user_local';
    const ADDRESS_ACC_1_USER_DEEP = 'address_account1_user_deep';
    const ADDRESS_ACC_1_2_USER_DEEP = 'address_account1_2_user_deep';
    const ADDRESS_ACC_1_1_USER_LOCAL = 'address_account1.1_user_local';
    const ADDRESS_ACC_2_USER_LOCAL = 'address_account2_user_local';

    /**
     * @var array
     */
    protected static $addresses = [
        self::ADDRESS_ACC_1_USER_LOCAL =>
        [
            'account' => 'account.level_1.1',
            'label' => 'account.level_1_local.address_2',
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        self::ADDRESS_ACC_1_USER_DEEP =>
        [
            'account' => 'account.level_1.1',
            'label' => 'account.level_1_deep.address_3',
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        self::ADDRESS_ACC_1_2_USER_DEEP => [
            'account' => 'account.level_1.1.2',
            'label' => 'account.level_1_2_deep.address_1',
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        self::ADDRESS_ACC_1_1_USER_LOCAL =>
        [
            'account' => 'account.level_1.1.1',
            'label' => 'account.level_1_1_local.address_4',
            'street' => '1167 Marion Drive',
            'city' => 'Winter Haven',
            'postalCode' => '33830',
            'country' => 'US',
            'region' => 'FL',
            'primary' => false,
            'types' => [],
            'defaults' => []
        ],
        self::ADDRESS_ACC_2_USER_LOCAL =>
        [
            'account' => 'account.level_1.2',
            'label' => 'account.level_2_local.address_5',
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
            LoadAccountAddressACLData::class,
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$addresses as $name => $addressData) {
            $address = new AccountAddress();
            $address->setSystemOrganization($this->getOrganization($manager));
            $address->setFrontendOwner($this->getReference($addressData['account']));
            $this->addAddress($manager, $addressData, $address);
        }

        $manager->flush();
    }
}
