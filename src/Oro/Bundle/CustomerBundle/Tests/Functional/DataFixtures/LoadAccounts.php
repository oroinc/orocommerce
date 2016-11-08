<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;

class LoadAccounts extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const DEFAULT_ACCOUNT_NAME = 'account.orphan';

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
     * account.orphan
     * account.level_1
     *     account.level_1.1
     *         account.level_1.1.1
     *         account.level_1.1.2
     *     account.level_1.2
     *         account.level_1.2.1
     *             account.level_1.2.1.1
     *     account.level_1.3
     *         account.level_1.3.1
     *             account.level_1.3.1.1
     *     account.level_1.4
     *         account.level_1.4.1
     *             account.level_1.4.1.1
     * account.level_1_1
     */
    public function load(ObjectManager $manager)
    {
        $owner = $this->getFirstUser($manager);

        $this->createAccount($manager, self::DEFAULT_ACCOUNT_NAME, $owner);

        $group1 = $this->getAccountGroup('account_group.group1');
        $group2 = $this->getAccountGroup('account_group.group2');
        $group3 = $this->getAccountGroup('account_group.group3');

        $levelOne = $this->createAccount($manager, 'account.level_1', $owner, null, $group1);

        $levelTwoFirst = $this->createAccount($manager, 'account.level_1.1', $owner, $levelOne);
        $this->createAccount($manager, 'account.level_1.1.1', $owner, $levelTwoFirst);
        $this->createAccount($manager, 'account.level_1.1.2', $owner, $levelTwoFirst);

        $levelTwoSecond = $this->createAccount($manager, 'account.level_1.2', $owner, $levelOne, $group2);
        $levelTreeFirst = $this->createAccount($manager, 'account.level_1.2.1', $owner, $levelTwoSecond, $group2);
        $this->createAccount($manager, 'account.level_1.2.1.1', $owner, $levelTreeFirst, $group2);

        $levelTwoThird = $this->createAccount($manager, 'account.level_1.3', $owner, $levelOne, $group1);
        $levelTreeFirst = $this->createAccount($manager, 'account.level_1.3.1', $owner, $levelTwoThird, $group3);
        $this->createAccount($manager, 'account.level_1.3.1.1', $owner, $levelTreeFirst, $group3);

        $levelTwoFourth = $this->createAccount($manager, 'account.level_1.4', $owner, $levelOne, $group3);
        $levelTreeFourth = $this->createAccount($manager, 'account.level_1.4.1', $owner, $levelTwoFourth);
        $this->createAccount($manager, 'account.level_1.4.1.1', $owner, $levelTreeFourth);

        $this->createAccount($manager, 'account.level_1_1', $owner);

        $manager->flush();
    }

    /**
     * @param string $reference
     * @return AccountGroup
     */
    protected function getAccountGroup($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param User $owner
     * @param Account $parent
     * @param AccountGroup $group
     * @return Account
     */
    protected function createAccount(
        ObjectManager $manager,
        $name,
        User $owner,
        Account $parent = null,
        AccountGroup $group = null
    ) {
        $account = new Account();
        $account->setName($name);
        $account->setOwner($owner);
        $account->setOrganization($owner->getOrganization());
        if ($parent) {
            $account->setParent($parent);
        }
        if ($group) {
            $account->setGroup($group);
        }
        $manager->persist($account);
        $this->addReference($name, $account);

        return $account;
    }
}
