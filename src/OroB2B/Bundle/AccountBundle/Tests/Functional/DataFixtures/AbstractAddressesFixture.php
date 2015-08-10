<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\AccountBundle\Entity\AbstractDefaultTypedAddress;

abstract class AbstractAddressesFixture extends AbstractFixture
{
    /**
     * @param EntityManager $manager
     * @param array $addressData
     * @param AbstractDefaultTypedAddress $address
     */
    protected function addAddress(EntityManager $manager, array $addressData, AbstractDefaultTypedAddress $address)
    {
        $defaults = [];
        foreach ($addressData['types'] as $type => $isDefault) {
            /** @var AddressType $addressType */
            $addressType = $manager->getReference('Oro\Bundle\AddressBundle\Entity\AddressType', $type);
            $address->addType($addressType);
            if ($isDefault) {
                $defaults[] = $addressType;
            }
        }

        /** @var Country $country */
        $country = $manager->getReference('OroAddressBundle:Country', $addressData['country']);
        /** @var Region $region */
        $region = $manager->getReference(
            'OroAddressBundle:Region',
            $addressData['country'] . '-' . $addressData['region']
        );

        $address->setDefaults($defaults);
        $address->setPrimary($addressData['primary'])
            ->setStreet($addressData['street'])
            ->setCity($addressData['city'])
            ->setPostalCode($addressData['postalCode'])
            ->setCountry($country)
            ->setRegion($region);

        $manager->persist($address);
        $this->addReference($addressData['label'], $address);
    }
}
