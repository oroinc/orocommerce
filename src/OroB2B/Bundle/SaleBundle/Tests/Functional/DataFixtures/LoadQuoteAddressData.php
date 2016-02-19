<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\SaleBundle\Entity\QuoteAddress;
use OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class LoadQuoteAddressData extends AbstractFixture implements DependentFixtureInterface
{
    const QUOTE_ADDRESS_1 = 'quote_address.office';

    /**
     * @var array
     */
    protected $addresses = [
        self::QUOTE_ADDRESS_1 => [
            'quote' => LoadQuoteData::QUOTE3,
            'type' => 'shipping',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
        ]
    ];

//    const COMPANY_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
//    const COMPANY_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
//    const COMPANY_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';
//
//    const USER_BILLING_DEFAULT_BILLING = '2413 Capitol Avenue, ROMNEY IN US 47981';
//    const USER_BILLING_SHIPPING_DEFAULT_SHIPPING = '1215 Caldwell Road, ROCHESTER NY US 14608';
//    const USER_BILLING_SHIPPING_ADDRESS = '722 Harvest Lane, SEDALIA MO US 65301';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $name => $address) {
            /** @var Order $order */
            $order = $this->getReference($address['quote']);
            $orderAddress = $this->createQuoteAddress($manager, $name, $address);

            $order->setShippingAddress($orderAddress);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param array $address
     * @return QuoteAddress
     */
    protected function createQuoteAddress(ObjectManager $manager, $name, array $address)
    {
        /** @var Country $country */
        $country = $manager->getReference('OroAddressBundle:Country', $address['country']);
        if (!$country) {
            throw new \RuntimeException('Can\'t find country with ISO ' . $address['country']);
        }

        /** @var Region $region */
        $region = $manager->getReference('OroAddressBundle:Region', $address['region']);
        if (!$region) {
            throw new \RuntimeException(
                sprintf('Can\'t find region with code %s', $address['country'])
            );
        }

        $orderAddress = new QuoteAddress();
        $orderAddress
            ->setCountry($country)
            ->setCity($address['city'])
            ->setRegion($region)
            ->setStreet($address['street'])
            ->setPostalCode($address['postalCode'])
            ->
        $manager->persist($orderAddress);
        $this->addReference($name, $orderAddress);

        return $orderAddress;
    }
}
