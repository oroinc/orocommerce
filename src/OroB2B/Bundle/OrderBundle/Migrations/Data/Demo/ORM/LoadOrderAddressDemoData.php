<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class LoadOrderAddressDemoData extends AbstractFixture
{
    const ORDER_ADDRESS_1 = 'Billing Address';
    const ORDER_ADDRESS_2 = 'Shipping Address';

    /**
     * @var array
     */
    protected $countries = [];

    /**
     * @var array
     */
    protected $regions = [];

    /**
     * @var array
     */
    protected $addresses = [
        [
            'label' => self::ORDER_ADDRESS_1,
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
        ],
        [
            'label' => self::ORDER_ADDRESS_2,
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $address) {
            $this->createOrderAddress($manager, $address);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $address
     */
    protected function createOrderAddress(ObjectManager $manager, array $address)
    {
        $orderAddress = new OrderAddress();
        $orderAddress
            ->setLabel($address['label'])
            ->setCountry($this->getCountryByIso2Code($manager, $address['country']))
            ->setCity($address['city'])
            ->setRegion($this->getRegionByIso2Code($manager, $address['region']))
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode']);

        $manager->persist($orderAddress);
    }

    /**
     * @param ObjectManager $manager
     * @param string $iso2Code
     * @return Country|null
     */
    protected function getCountryByIso2Code(ObjectManager $manager, $iso2Code)
    {
        if (!array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getRepository('OroAddressBundle:Country')
                ->findOneBy(['iso2Code' => $iso2Code]);
        }

        return $this->countries[$iso2Code];
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @return Region|null
     */
    protected function getRegionByIso2Code(ObjectManager $manager, $code)
    {
        if (!array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getRepository('OroAddressBundle:Region')
                ->findOneBy(['code' => $code]);
        }

        return $this->regions[$code];
    }
}
