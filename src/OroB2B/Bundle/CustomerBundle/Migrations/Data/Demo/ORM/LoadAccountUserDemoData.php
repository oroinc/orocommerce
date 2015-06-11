<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

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
        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroB2BCustomerBundle/Migrations/Data/Demo/ORM/data/account-users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            // create account user
            $accountUser = new AccountUser();
            $accountUser
                ->setUsername($row['email'])
                ->setEmail($row['email'])
                ->setPassword($row['email'])
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setEnabled(true);

            $manager->persist($accountUser);
        }

        fclose($handler);

        $manager->flush();
    }
}
