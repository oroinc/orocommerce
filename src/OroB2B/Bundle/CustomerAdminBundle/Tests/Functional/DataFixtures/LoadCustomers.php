<?php

namespace OroB2B\Bundle\CustomerAdminBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerAdminBundle\Entity\Customer;

class LoadCustomers extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createCustomer($manager, 'orphan');

        $levelOne = $this->createCustomer($manager, 'level_1');

        $levelTwoFirst = $this->createCustomer($manager, 'level_1.1', $levelOne);
        $this->createCustomer($manager, 'level_1.1.1', $levelTwoFirst);

        $this->createCustomer($manager, 'level_1.2', $levelOne);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param Customer $parent
     * @return Customer
     */
    protected function createCustomer(ObjectManager $manager, $name, Customer $parent = null)
    {
        $customer = new Customer();
        $customer->setName($name);
        if ($parent) {
            $customer->setParent($parent);
        }
        $manager->persist($customer);
        $this->addReference($name, $customer);

        return $customer;
    }
}