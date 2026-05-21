<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * Creates one CustomerUserAddress with default billing and shipping types
 * for the customer user ACCOUNT1_USER1 used by draft-session functional tests.
 */
class LoadCustomerUserAddressesForDraftData extends AbstractFixture implements DependentFixtureInterface
{
    public const ADDRESS_1 = 'rfp.draft_test.customer_user_address.1';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadRequestData::class,
            LoadOrganization::class,
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadUserData::ACCOUNT1_USER1);

        /** @var \Oro\Bundle\OrganizationBundle\Entity\Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $billingType = $manager->getReference(AddressType::class, AddressType::TYPE_BILLING);
        $shippingType = $manager->getReference(AddressType::class, AddressType::TYPE_SHIPPING);

        /** @var Country $country */
        $country = $manager->getReference(Country::class, 'US');

        /** @var Region $region */
        $region = $manager->getReference(Region::class, 'US-NY');

        $address = new CustomerUserAddress();
        $address->setSystemOrganization($organization);
        $address->setFrontendOwner($customerUser);
        $address->setPrimary(true);
        $address->setLabel(self::ADDRESS_1);
        $address->setStreet('1215 Caldwell Road');
        $address->setCity('Rochester');
        $address->setPostalCode('14608');
        $address->setCountry($country);
        $address->setRegion($region);
        $address->setOrganization('Test Org');

        $address->addType($billingType);
        $address->addType($shippingType);
        $address->setDefaults([$billingType, $shippingType]);

        $manager->persist($address);
        $manager->flush();

        $this->setReference(self::ADDRESS_1, $address);
    }
}
