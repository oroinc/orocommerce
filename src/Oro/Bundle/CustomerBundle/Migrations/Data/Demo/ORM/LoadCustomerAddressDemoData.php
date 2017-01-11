<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;

class LoadCustomerAddressDemoData extends AbstractLoadAddressDemoData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerUserDemoData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroCustomerBundle/Migrations/Data/Demo/ORM/data/customer-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        /** @var CustomerUser[] $customerUsers */
        $customerUsers = $this->getDemoCustomerUsers();

        /** @var CustomerUser[] $customerUserByEmail */
        $customerUserByEmail = [];
        foreach ($customerUsers as $customerUser) {
            $customerUserByEmail[$customerUser->getEmail()] = $customerUser;
        }

        $customerHasAddress = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $customerUser = $customerUserByEmail[$row['email']];
            if (isset($customerHasAddress[$customerUser->getCustomer()->getId()])) {
                continue;
            }
            $customerUser
                ->getCustomer()
                ->addAddress($this->createAddress($row));
            $customerHasAddress[$customerUser->getCustomer()->getId()] = true;
        }

        fclose($handler);
        $manager->flush();
    }

    /**
     * @return CustomerUser[]
     */
    protected function getDemoCustomerUsers()
    {
        $customerUsers = [];
        foreach (LoadCustomerUserDemoData::$customerUsersReferencesNames as $referenceName) {
            $customerUsers[] = $this->getReference($referenceName);
        }

        return $customerUsers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewAddressEntity()
    {
        return new CustomerAddress();
    }
}
