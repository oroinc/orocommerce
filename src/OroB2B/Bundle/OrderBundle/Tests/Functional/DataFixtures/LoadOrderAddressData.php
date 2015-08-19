<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;

class LoadOrderAddressData extends AbstractFixture implements DependentFixtureInterface
{
    const ORDER_ADDRESS_1 = 'order_address.office';
    const ORDER_ADDRESS_2 = 'order_address.warehouse';

    /**
     * @var array
     */
    protected $addresses = [
        self::ORDER_ADDRESS_1 => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'billing',
            'country' => 'US',
            'city' => 'Rochester',
            'region' => 'US-NY',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
        ],
        self::ORDER_ADDRESS_2 => [
            'order' => LoadOrders::ORDER_1,
            'type' => 'shipping',
            'country' => 'US',
            'city' => 'Romney',
            'region' => 'US-IN',
            'street' => '2413 Capitol Avenue',
            'postalCode' => '47981',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addresses as $name => $address) {
            /** @var Order $order */
            $order = $this->getReference($address['order']);
            $orderAddress = $this->createOrderAddress($manager, $name, $address);

            if ($address['type'] === 'billing') {
                $order->setBillingAddress($orderAddress);
            } else {
                $order->setShippingAddress($orderAddress);
            }
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
