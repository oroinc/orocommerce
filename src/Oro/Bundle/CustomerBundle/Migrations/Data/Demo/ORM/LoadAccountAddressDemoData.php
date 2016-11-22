<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountAddress;

class LoadAccountAddressDemoData extends AbstractLoadAddressDemoData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        /** @var AccountUser[] $accountUsers */
        $accountUsers = $this->getDemoAccountUsers();

        /** @var AccountUser[] $accountUserByEmail */
        $accountUserByEmail = [];
        foreach ($accountUsers as $accountUser) {
            $accountUserByEmail[$accountUser->getEmail()] = $accountUser;
        }

        $accountHasAddress = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $accountUser = $accountUserByEmail[$row['email']];
            if (isset($accountHasAddress[$accountUser->getAccount()->getId()])) {
                continue;
            }
            $accountUser
                ->getAccount()
                ->addAddress($this->createAddress($row));
            $accountHasAddress[$accountUser->getAccount()->getId()] = true;
        }

        fclose($handler);
        $manager->flush();
    }

    /**
     * @return AccountUser[]
     */
    protected function getDemoAccountUsers()
    {
        $accountUsers = [];
        foreach (LoadAccountUserDemoData::$accountUsersReferencesNames as $referenceName) {
            $accountUsers[] = $this->getReference($referenceName);
        }

        return $accountUsers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewAddressEntity()
    {
        return new AccountAddress();
    }
}
