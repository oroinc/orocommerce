<?php

namespace OroB2B\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomers extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [__NAMESPACE__ . '\LoadGroups'];
    }

    /**
     * {@inheritdoc}
     *
     * customer.orphan
     * customer.level_1
     *     customer.level_1.1
     *         customer.level_1.1.1
     *     customer.level_1.2
     *         customer.level_1.2.1
     *             customer.level_1.2.1.1
     *     customer.level_1.3
     *         customer.level_1.3.1
     *             customer.level_1.3.1.1
     *     customer.level_1.4
     */
    public function load(ObjectManager $manager)
    {
        $this->createCustomer($manager, 'customer.orphan');

        $levelOne = $this->createCustomer($manager, 'customer.level_1');

        $levelTwoFirst = $this->createCustomer($manager, 'customer.level_1.1', $levelOne);
        $this->createCustomer($manager, 'customer.level_1.1.1', $levelTwoFirst);

        $levelTwoSecond = $this->createCustomer($manager, 'customer.level_1.2', $levelOne);
        $levelTreeFirst = $this->createCustomer($manager, 'customer.level_1.2.1', $levelTwoSecond);
        $this->createCustomer($manager, 'customer.level_1.2.1.1', $levelTreeFirst);

        $levelTwoThird = $this->createCustomer(
            $manager,
            'customer.level_1.3',
            $levelOne,
            $this->getCustomerGroup('customer_group.group1')
        );
        $levelTreeFirst = $this->createCustomer($manager, 'customer.level_1.3.1', $levelTwoThird);
        $this->createCustomer($manager, 'customer.level_1.3.1.1', $levelTreeFirst);

        $this->createCustomer($manager, 'customer.level_1.4', $levelOne);

        $manager->flush();
    }

    /**
     * @param string $reference
     * @return CustomerGroup
     */
    protected function getCustomerGroup($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param Customer $parent
     * @param CustomerGroup $group
     * @return Customer
     */
    protected function createCustomer(
        ObjectManager $manager,
        $name,
        Customer $parent = null,
        CustomerGroup $group = null
    ) {
        $customer = new Customer();
        $customer->setName($name);
        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $customer->setOrganization($organization);
        if ($parent) {
            $customer->setParent($parent);
        }
        if ($group) {
            $customer->setGroup($group);
        }
        $manager->persist($customer);
        $this->addReference($name, $customer);

        return $customer;
    }
}
