<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;
use OroB2B\Bundle\CustomerAdminBundle\Entity\CustomerGroup;
use OroB2B\Bundle\UserAdminBundle\Entity\User;

class LoadCustomerDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['OroB2B\Bundle\UserAdminBundle\Migrations\Data\Demo\ORM\LoadB2BUserDemoData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User[] $users */
        $users = $manager->getRepository('OroB2BUserAdminBundle:User')->findAll();

        $rootCustomer = null;
        $firstLevelCustomer = null;

        // create customer groups
        $rootGroup = $this->createCustomerGroup('Root');
        $firstLevelGroup = $this->createCustomerGroup('First');
        $secondLevelGroup = $this->createCustomerGroup('Second');

        $manager->persist($rootGroup);
        $manager->persist($firstLevelGroup);
        $manager->persist($secondLevelGroup);

        // create customers
        foreach ($users as $index => $user) {
            $customer = new Customer();
            $customer->setName($user->getFirstName() . ' ' . $user->getLastName());

            switch ($index % 3) {
                case 0:
                    $customer->setGroup($rootGroup);
                    $rootCustomer = $customer;
                    break;
                case 1:
                    $customer->setGroup($firstLevelGroup)
                        ->setParent($rootCustomer);
                    $firstLevelCustomer = $customer;
                    break;
                case 2:
                    $customer->setGroup($secondLevelGroup)
                        ->setParent($firstLevelCustomer);
                    break;
            }

            $manager->persist($customer);
            $user->setCustomer($customer);
        }

        $manager->flush();
    }

    /**
     * @param string $name
     * @return CustomerGroup
     */
    protected function createCustomerGroup($name)
    {
        $customerGroup = new CustomerGroup();
        $customerGroup->setName($name);

        return $customerGroup;
    }
}
