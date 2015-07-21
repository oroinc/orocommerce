<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var ObjectRepository|EntityRepository */
    protected $countryRepository;

    /** @var ObjectRepository|EntityRepository */
    protected $regionRepository;
    
    /** @var ObjectRepository|EntityRepository */
    protected $addressTypeRepository;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('orob2b_account_user.manager');
        $this->countryRepository = $manager->getRepository('OroAddressBundle:Country');
        $this->regionRepository = $manager->getRepository('OroAddressBundle:Region');
        $this->addressTypeRepository = $manager->getRepository('OroAddressBundle:AddressType');

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $organizations = $manager->getRepository('OroOrganizationBundle:Organization')->findAll();
        $organization = reset($organizations);

        $storageManager = $userManager->getStorageManager();

        $roles = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            // create/get account user role
            $roleLabel = $row['role'];
            if (!array_key_exists($roleLabel, $roles)) {
                $newRole = new AccountUserRole();
                $newRole->setLabel($roleLabel)
                    ->setRole($roleLabel);

                $storageManager->persist($newRole);

                $roles[$roleLabel] = $newRole;
            }
            $role = $roles[$roleLabel];

            // create account user
            /** @var AccountUser $accountUser */
            $accountUser = $userManager->createUser();
            $accountUser
                ->setUsername($row['email'])
                ->setEmail($row['email'])
                ->setPassword($row['email'])
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setPlainPassword(md5(uniqid(mt_rand(), true)))
                ->setEnabled(true)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setLoginCount(0)
                ->addRole($role)
                ->addAddress($this->createAccountUserAddress($row));

            $userManager->updateUser($accountUser, false);
        }

        fclose($handler);

        $storageManager->flush();
    }

    private function createAccountUserAddress($data)
    {
        /** @var Country $country */
        $country = $this->countryRepository->findOneBy(['iso2Code' => $data['country']]);
        if (!$country) {
            throw new \RuntimeException('Can\'t find country with ISO ' . $data['country']);
        }

        /** @var Region $region */
        $region = $this->regionRepository->findOneBy(['country' => $country, 'code' => $data['state']]);
        if (!$region) {
            throw new \RuntimeException(
                printf('Can\'t find region with country ISO %s and code %s', $data['country'], $data['state'])
            );
        }

        $types = [];
        $typesFromData = explode(',', $data['types']);
        foreach ($typesFromData as $type) {
            $types[] = $this->addressTypeRepository->find($type);
        }

        $defaultTypes = [];
        $defaultTypesFromData = explode(',', $data['defaultTypes']);
        foreach ($defaultTypesFromData as $defaultType) {
            $defaultTypes[] = $this->addressTypeRepository->find($defaultType);
        }

        $address = new AccountUserAddress();
        $address
            ->setPrimary(true)
            ->setLabel('Primary address')
            ->setCountry($country)
            ->setStreet($data['street'])
            ->setCity($data['city'])
            ->setRegion($region)
            ->setPostalCode($data['zipCode'])
            ->setTypes(new ArrayCollection($types))
            ->setDefaults(new ArrayCollection($defaultTypes));

        return $address;
    }
}
