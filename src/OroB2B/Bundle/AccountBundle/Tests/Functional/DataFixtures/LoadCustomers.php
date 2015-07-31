<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class LoadCustomers extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createCustomer($manager, 'account.orphan');

        $levelOne = $this->createCustomer($manager, 'account.level_1');

        $levelTwoFirst = $this->createCustomer($manager, 'account.level_1.1', $levelOne);
        $this->createCustomer($manager, 'account.level_1.1.1', $levelTwoFirst);

        $this->createCustomer($manager, 'account.level_1.2', $levelOne);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param Account $parent
     * @return Account
     */
    protected function createCustomer(ObjectManager $manager, $name, Account $parent = null)
    {
        $customer = new Account();
        $customer->setName($name);
        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $customer->setOrganization($organization);
        if ($parent) {
            $customer->setParent($parent);
        }
        $manager->persist($customer);
        $this->addReference($name, $customer);

        return $customer;
    }
}
