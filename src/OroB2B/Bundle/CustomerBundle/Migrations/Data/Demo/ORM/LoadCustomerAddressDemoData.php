<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress;

class LoadCustomerAddressDemoData extends AbstractLoadAddressDemoData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        /** @var AccountUser[] $accountUsers */
        $accountUsers = $manager->getRepository('OroB2BCustomerBundle:AccountUser')->findAll();
        /** @var AccountUser[] $accountUserByEmail */
        $accountUserByEmail = [];
        foreach ($accountUsers as $accountUser) {
            $accountUserByEmail[$accountUser->getEmail()] = $accountUser;
        }

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $accountUser = $accountUserByEmail[$row['email']];
            $accountUser
                ->getCustomer()
                ->addAddress($this->createAddress($row));
        }

        fclose($handler);
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewAddressEntity()
    {
        return new CustomerAddress();
    }
}
