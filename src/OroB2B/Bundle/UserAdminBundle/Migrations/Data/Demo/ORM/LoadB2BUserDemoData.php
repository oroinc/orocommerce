<?php

namespace OroB2B\Bundle\UserAdminBundle\Migrations\Data\Demo\ORM;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\UserAdminBundle\Entity\Group;
use OroB2B\Bundle\UserAdminBundle\Entity\User;

class LoadB2BUserDemoData extends AbstractFixture implements ContainerAwareInterface
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
        $filePath = $locator->locate('@OroB2BUserAdminBundle/Migrations/Data/Demo/ORM/data/users.csv');
        if (is_array($filePath)) {
            $filePath = current($filePath);
        }

        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');

        $groups = [];

        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));

            // create group
            $groupName = $row['group'];
            if (empty($groups[$groupName])) {
                $groups[$groupName] = new Group($groupName);
                $manager->persist($groups[$groupName]);
            }
            $group = $groups[$groupName];

            // create user
            $user = new User();
            $user->setEmail($row['email'])
                ->setPassword($row['email'])
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setEnabled(true)
                ->addGroup($group);
            $manager->persist($user);
        }

        fclose($handler);

        $manager->flush();
    }
}
