<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

class LoadCustomers extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const DEFAULT_ACCOUNT_NAME = 'customer.orphan';

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
     *         customer.level_1.1.2
     *     customer.level_1.2
     *         customer.level_1.2.1
     *             customer.level_1.2.1.1
     *     customer.level_1.3
     *         customer.level_1.3.1
     *             customer.level_1.3.1.1
     *     customer.level_1.4
     *         customer.level_1.4.1
     *             customer.level_1.4.1.1
     * customer.level_1_1
     */
    public function load(ObjectManager $manager)
    {
        $owner = $this->getFirstUser($manager);

        $this->createCustomer($manager, self::DEFAULT_ACCOUNT_NAME, $owner);

        $group1 = $this->getCustomerGroup('customer_group.group1');
        $group2 = $this->getCustomerGroup('customer_group.group2');
        $group3 = $this->getCustomerGroup('customer_group.group3');

        $levelOne = $this->createCustomer($manager, 'customer.level_1', $owner, null, $group1);

        $levelTwoFirst = $this->createCustomer($manager, 'customer.level_1.1', $owner, $levelOne);
        $this->createCustomer($manager, 'customer.level_1.1.1', $owner, $levelTwoFirst);
        $this->createCustomer($manager, 'customer.level_1.1.2', $owner, $levelTwoFirst);

        $levelTwoSecond = $this->createCustomer($manager, 'customer.level_1.2', $owner, $levelOne, $group2);
        $levelTreeFirst = $this->createCustomer($manager, 'customer.level_1.2.1', $owner, $levelTwoSecond, $group2);
        $this->createCustomer($manager, 'customer.level_1.2.1.1', $owner, $levelTreeFirst, $group2);

        $levelTwoThird = $this->createCustomer($manager, 'customer.level_1.3', $owner, $levelOne, $group1);
        $levelTreeFirst = $this->createCustomer($manager, 'customer.level_1.3.1', $owner, $levelTwoThird, $group3);
        $this->createCustomer($manager, 'customer.level_1.3.1.1', $owner, $levelTreeFirst, $group3);

        $levelTwoFourth = $this->createCustomer($manager, 'customer.level_1.4', $owner, $levelOne, $group3);
        $levelTreeFourth = $this->createCustomer($manager, 'customer.level_1.4.1', $owner, $levelTwoFourth);
        $this->createCustomer($manager, 'customer.level_1.4.1.1', $owner, $levelTreeFourth);

        $this->createCustomer($manager, 'customer.level_1_1', $owner);

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
     * @param User $owner
     * @param Customer $parent
     * @param CustomerGroup $group
     * @return Customer
     */
    protected function createCustomer(
        ObjectManager $manager,
        $name,
        User $owner,
        Customer $parent = null,
        CustomerGroup $group = null
    ) {
        $customer = new Customer();
        $customer->setName($name);
        $customer->setOwner($owner);
        $customer->setOrganization($owner->getOrganization());
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
