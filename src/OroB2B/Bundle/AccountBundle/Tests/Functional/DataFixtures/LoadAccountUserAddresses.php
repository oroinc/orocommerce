<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserAddress;

class LoadAccountUserAddresses extends AbstractAddressesFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $addresses = [
        [
            'account_user' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'label' => 'grzegorz.brzeczyszczykiewicz@example.com.address_1',
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        [
            'account_user' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'label' => 'grzegorz.brzeczyszczykiewicz@example.com.address_2',
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        [
            'account_user' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'label' => 'grzegorz.brzeczyszczykiewicz@example.com.address_3',
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        [
            'account_user' => 'grzegorz.brzeczyszczykiewicz@example.com',
            'label' => 'grzegorz.brzeczyszczykiewicz@example.com.address_4',
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
            'account_user' => 'other.user@test.com',
            'label' => 'other.user@test.com.address_1',
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
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData'
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
            $address->setOwner($this->getReference($addressData['account_user']));
            $this->addAddress($manager, $addressData, $address);
        }

        $manager->flush();
    }
}
