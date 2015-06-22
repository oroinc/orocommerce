<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\AccountUserRole;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadAccountUserDemoData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

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
                ->addRole($role);

            $userManager->updateUser($accountUser, false);
        }

        fclose($handler);

        $storageManager->flush();
    }
}
