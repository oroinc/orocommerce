<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class LoadAccounts extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createAccount($manager, 'account.orphan');

        $levelOne = $this->createAccount($manager, 'account.level_1');

        $levelTwoFirst = $this->createAccount($manager, 'account.level_1.1', $levelOne);
        $this->createAccount($manager, 'account.level_1.1.1', $levelTwoFirst);

        $this->createAccount($manager, 'account.level_1.2', $levelOne);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param Account $parent
     * @return Account
     */
    protected function createAccount(ObjectManager $manager, $name, Account $parent = null)
    {
        $account = new Account();
        $account->setName($name);
        $organization = $manager
            ->getRepository('OroOrganizationBundle:Organization')
            ->getFirst();
        $account->setOrganization($organization);
        if ($parent) {
            $account->setParent($parent);
        }
        $manager->persist($account);
        $this->addReference($name, $account);

        return $account;
    }
}
