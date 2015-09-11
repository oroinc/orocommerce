<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoleData extends AbstractFixture implements DependentFixtureInterface
{
    const ROLE_WITH_ACCOUNT_USER = 'Role with account user';
    const ROLE_WITH_ACCOUNT = 'Role with account';
    const ROLE_WITH_WEBSITE = 'Role with website';
    const ROLE_WITHOUT_USER_AND_WEBSITE = 'Role without user and website';
    const ROLE_WITHOUT_ACCOUNT = 'Predefined role';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts'
        ];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->loadRoleWithAccountUser(
            $manager,
            self::ROLE_WITH_ACCOUNT_USER,
            'grzegorz.brzeczyszczykiewicz@example.com'
        );
        $this->loadRoleWithAccount($manager, self::ROLE_WITH_ACCOUNT, 'account.level_1');
        $this->loadRoleWithWebsite($manager, self::ROLE_WITH_WEBSITE, 'Canada');
        $this->loadRoleWithoutUserAndWebsite($manager, self::ROLE_WITHOUT_USER_AND_WEBSITE);
        $this->loadRoleWithoutAccount($manager, self::ROLE_WITHOUT_ACCOUNT);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @param string $websiteName
     */
    protected function loadRoleWithWebsite(ObjectManager $manager, $roleLabel, $websiteName)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        $entity->setOrganization($organization);
        /** @var Website $website */
        $website = $this->getReference($websiteName);
        $entity->addWebsite($website);

        $this->setReference($entity->getLabel(), $entity);

        $manager->persist($entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @param string $accountUser
     */
    protected function loadRoleWithAccountUser(ObjectManager $manager, $roleLabel, $accountUser)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        $entity->setOrganization($organization);
        /** @var AccountUser $accountUser */
        $accountUser = $this->getReference($accountUser);
        $accountUser->addRole($entity);

        $this->setReference($entity->getLabel(), $entity);

        $manager->persist($entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @param string $account
     */
    protected function loadRoleWithAccount(ObjectManager $manager, $roleLabel, $account)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);

        /** @var Account $account */
        $account = $this->getReference($account);
        $entity->setAccount($account);
        $entity->setOrganization($account->getOrganization());

        $this->setReference($entity->getLabel(), $entity);

        $manager->persist($entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     */
    protected function loadRoleWithoutUserAndWebsite(ObjectManager $manager, $roleLabel)
    {
        $entity = new AccountUserRole();
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
        $entity->setLabel($roleLabel);
        $entity->setOrganization($organization);
        $this->setReference($entity->getLabel(), $entity);

        $manager->persist($entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     */
    protected function loadRoleWithoutAccount(ObjectManager $manager, $roleLabel)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);

        $manager->persist($entity);
    }
}
