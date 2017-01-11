<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;

class LoadCustomerAddressesACLData extends AbstractAddressesFixture implements DependentFixtureInterface
{
    const ADDRESS_ACC_1_LEVEL_LOCAL = 'address_customer1_level_local';
    const ADDRESS_ACC_1_LEVEL_DEEP = 'address_customer1_level_deep';
    const ADDRESS_ACC_1_1_LEVEL_LOCAL = 'address_customer1.1_level_local';
    const ADDRESS_ACC_1_2_LEVEL_LOCAL = 'address_customer1.2_level_local';
    const ADDRESS_ACC_2_LEVEL_LOCAL = 'address_customer2_level_local';

    /**
     * @var array
     */
    protected $addresses = [
        [
            'customer' => 'customer.level_1.1',
            'label' => self::ADDRESS_ACC_1_LEVEL_LOCAL,
            'street' => '2413 Capitol Avenue',
            'city' => 'Romney',
            'postalCode' => '47981',
            'country' => 'US',
            'region' => 'IN',
            'primary' => false,
            'types' => ['billing' => true]
        ],
        [
            'customer' => 'customer.level_1.1',
            'label' => self::ADDRESS_ACC_1_LEVEL_DEEP,
            'street' => '722 Harvest Lane',
            'city' => 'Sedalia',
            'postalCode' => '65301',
            'country' => 'US',
            'region' => 'MO',
            'primary' => false,
            'types' => ['billing' => false, 'shipping' => false]
        ],
        [
            'customer' => 'customer.level_1.1.2',
            'label' => self::ADDRESS_ACC_1_2_LEVEL_LOCAL,
            'street' => '1215 Caldwell Road',
            'city' => 'Rochester',
            'postalCode' => '14608',
            'country' => 'US',
            'region' => 'NY',
            'primary' => true,
            'types' => ['billing' => false, 'shipping' => true]
        ],
        [
            'customer' => 'customer.level_1.1.1',
            'label' => self::ADDRESS_ACC_1_1_LEVEL_LOCAL,
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
            'customer' => 'customer.level_1.2',
            'label' => self::ADDRESS_ACC_2_LEVEL_LOCAL,
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
            LoadCustomerAddressACLData::class,
        ];
    }

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $addressData) {
            $address = new CustomerAddress();
            $address->setSystemOrganization($this->getOrganization($manager));
            $address->setFrontendOwner($this->getReference($addressData['customer']));
            $this->addAddress($manager, $addressData, $address);
        }

        $manager->flush();
    }
}
