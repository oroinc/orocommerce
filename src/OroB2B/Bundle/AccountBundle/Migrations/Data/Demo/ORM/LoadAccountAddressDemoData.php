<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountAddress;

class LoadAccountAddressDemoData extends AbstractLoadAddressDemoData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM\LoadAccountUserDemoData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BAccountBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
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
