<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserAddress;

class LoadAccountUserAddressDemoData extends AbstractLoadAddressDemoData implements DependentFixtureInterface
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

        $userManager = $this->container->get('orob2b_account_user.manager');

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            $referenceName = LoadAccountUserDemoData::ACCOUNT_USERS_REFERENCE_PREFIX . $row['email'];
            if (!$this->hasReference($referenceName)) {
                continue;
            }

            /** @var AccountUser $accountUser */
            $accountUser = $this->getReference($referenceName);
            $accountUser
                ->addAddress($this->createAddress($row));

            $userManager->updateUser($accountUser, false);
        }

        fclose($handler);
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewAddressEntity()
    {
        return new AccountUserAddress();
    }
}
