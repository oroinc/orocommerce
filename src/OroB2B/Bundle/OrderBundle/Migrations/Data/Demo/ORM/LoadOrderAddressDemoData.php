<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class LoadOrderAddressDemoData extends AbstractFixture
{
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
            'label' => 'Billing Address 01',
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'US-NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608'
        ],
        [
            'label' => 'Shipping Address 01',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981'
        ],
        [
            'label' => 'Billing Address 02',
            'country' => 'Canada',
            'city' => 'Toronto',
            'region' => 'CA-ON',
            'street' => '1900 Eglinton Ave E',
            'postalCode' => 'M3H'
        ],
        [
            'label' => 'Shipping Address 02',
            'country' => 'Canada',
            'city' => 'Toronto',
            'region' => 'CA-ON',
            'street' => '1120 Birchmount Rd',
            'postalCode' => 'M3C'
        ],
        [
            'label' => 'Billing Address 03',
            'country' => 'IT',
            'city' => 'Pisa',
            'region' => 'IT-52',
            'street' => 'Viale Benedetto Croce, 36',
            'postalCode' => '56125'
        ],
        [
            'label' => 'Shipping Address 03',
            'country' => 'IT',
            'city' => 'Livorno',
            'region' => 'IT-52',
            'street' => 'Piazza Grande',
            'postalCode' => '57123'
        ],
        [
            'label' => 'Billing Address 04',
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'US-NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608'
        ],
        [
            'label' => 'Shipping Address 04',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981'
        ],
        [
            'label' => 'Billing Address 05',
            'country' => 'Canada',
            'city' => 'Toronto',
            'region' => 'CA-ON',
            'street' => '1900 Eglinton Ave E',
            'postalCode' => 'M3H'
        ],
        [
            'label' => 'Shipping Address 05',
            'country' => 'Canada',
            'city' => 'Toronto',
            'region' => 'CA-ON',
            'street' => '1120 Birchmount Rd',
            'postalCode' => 'M3C'
        ],
    ];

    /**
     * @param EntityManager $manager
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $address) {
            $orderAddress = new OrderAddress();
            $orderAddress
                ->setLabel($address['label'])
                ->setCountry($this->getCountryByIso2Code($manager, $address['country']))
                ->setCity($address['city'])
                ->setRegion($this->getRegionByIso2Code($manager, $address['region']))
                ->setStreet($address['street'])
                ->setPostalCode($address['postalCode']);

            $this->addReference($address['label'], $orderAddress);

            $manager->persist($orderAddress);
        }

        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param string $iso2Code
     * @return Country|null
     */
    protected function getCountryByIso2Code(EntityManager $manager, $iso2Code)
    {
        if (!array_key_exists($iso2Code, $this->countries)) {
            $this->countries[$iso2Code] = $manager->getReference('OroAddressBundle:Country', $iso2Code);
        }

        return $this->countries[$iso2Code];
    }

    /**
     * @param EntityManager $manager
     * @param string $code
     * @return Region|null
     */
    protected function getRegionByIso2Code(EntityManager $manager, $code)
    {
        if (!array_key_exists($code, $this->regions)) {
            $this->regions[$code] = $manager->getReference('OroAddressBundle:Region', $code);
        }

        return $this->regions[$code];
    }
}
