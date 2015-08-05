<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class LoadOrderAddressData extends AbstractFixture
{
    const ORDER_ADDRESS_1 = 'order_address.1';
    const ORDER_ADDRESS_2 = 'order_address.2';
    const ORDER_ADDRESS_3 = 'order_address.3';
    const ORDER_ADDRESS_4 = 'order_address.4';
    const ORDER_ADDRESS_5 = 'order_address.5';
    const ORDER_ADDRESS_6 = 'order_address.6';
    const ORDER_ADDRESS_7 = 'order_address.7';
    const ORDER_ADDRESS_8 = 'order_address.8';

    /**
     * @var ObjectRepository
     */
    protected $countryRepository;

    /**
     * @var ObjectRepository
     */
    protected $regionRepository;

    /**
     * @var array
     */
    protected $addresses = [
        self::ORDER_ADDRESS_1 => [
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
        ],
        self::ORDER_ADDRESS_2 => [
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
        ],
        self::ORDER_ADDRESS_3 => [
            'country' => 'US',
            'city' => 'Sedalia',
            'region' => 'MO',
            'street' => '722 Harvest Lane',
            'postalCode' => '65301',
        ],
        self::ORDER_ADDRESS_4 => [
            'country' => 'US',
            'city' => 'Winter Haven',
            'region' => 'FL',
            'street' => '1167 Marion Drive',
            'postalCode' => '33830',
        ],
        self::ORDER_ADDRESS_5 => [
            'country' => 'US',
            'city' => 'Fort Myers',
            'region' => 'FL',
            'street' => '988 Sunburst Drive',
            'postalCode' => '33901',
        ],
        self::ORDER_ADDRESS_6 => [
            'country' => 'US',
            'city' => 'Albany',
            'region' => 'GA',
            'street' => '2849 Junkins Avenue',
            'postalCode' => '31707',
        ],
        self::ORDER_ADDRESS_7 => [
            'country' => 'US',
            'city' => 'Queens',
            'region' => 'NY',
            'street' => '643 Patterson Road',
            'postalCode' => '11418',
        ],
        self::ORDER_ADDRESS_8 => [
            'country' => 'US',
            'city' => 'Desert Center',
            'region' => 'CA',
            'street' => '180 Tenmile',
            'postalCode' => '92239',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->countryRepository = $manager->getRepository('OroAddressBundle:Country');
        $this->regionRepository = $manager->getRepository('OroAddressBundle:Region');

        foreach ($this->addresses as $name => $address) {
            $this->createOrderAddress($manager, $name, $address);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $address
     * @return OrderAddress
     */
    protected function createOrderAddress(ObjectManager $manager, $name, array $address)
    {
        /** @var Country $country */
        $country = $this->countryRepository->findOneBy(['iso2Code' => $address['country']]);
        if (!$country) {
            throw new \RuntimeException('Can\'t find country with ISO ' . $address['country']);
        }

        /** @var Region $region */
        $region = $this->regionRepository->findOneBy(['country' => $country, 'code' => $address['region']]);
        if (!$region) {
            throw new \RuntimeException(
                printf('Can\'t find region with country ISO %s and code %s', $address['country'], $address['region'])
            );
        }

        $orderAddress = new OrderAddress();
        $orderAddress
            ->setCountry($country)
            ->setCity($address['city'])
            ->setRegion($region)
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode']);

        $manager->persist($orderAddress);
        $this->addReference($name, $orderAddress);

        return $orderAddress;
    }
}
