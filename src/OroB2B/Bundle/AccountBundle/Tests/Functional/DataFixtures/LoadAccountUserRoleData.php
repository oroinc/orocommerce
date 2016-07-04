<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class LoadAccountUserRoleData extends AbstractFixture implements DependentFixtureInterface
{
    const ROLE_WITH_ACCOUNT_USER = 'Role with account user';
    const ROLE_WITH_ACCOUNT = 'Role with account';
    const ROLE_WITH_WEBSITE = 'Role with website';
    const ROLE_EMPTY = 'Role without any additional attributes';
    const ROLE_NOT_SELF_MANAGED = 'Role that is not self managed';

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
        $this->loadEmptyRole($manager, self::ROLE_EMPTY);
        $this->loadNotSelfManagedRole($manager, self::ROLE_NOT_SELF_MANAGED);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @param string $websiteName
     */
    protected function loadRoleWithWebsite(ObjectManager $manager, $roleLabel, $websiteName)
    {
        $entity = $this->loadEmptyRole($manager, $roleLabel);

        /** @var Website $website */
        $website = $this->getReference($websiteName);
        $entity->addWebsite($website);
        $entity->setSelfManaged(true);

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
        $entity = $this->loadEmptyRole($manager, $roleLabel);
        $entity->setSelfManaged(true);

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
        $entity = $this->loadEmptyRole($manager, $roleLabel);

        /** @var Account $account */
        $account = $this->getReference($account);
        $entity->setAccount($account);
        $entity->setSelfManaged(true);

        $this->setReference($entity->getLabel(), $entity);
        $manager->persist($entity);
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @return AccountUserRole
     */
    protected function loadEmptyRole(ObjectManager $manager, $roleLabel)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);
        $entity->setOrganization($this->getDefaultOrganization($manager));
        $entity->setSelfManaged(true);
        $this->setReference($entity->getLabel(), $entity);
        $manager->persist($entity);

        return $entity;
    }

    /**
     * @param ObjectManager $manager
     * @param string $roleLabel
     * @return AccountUserRole
     */
    protected function loadNotSelfManagedRole(ObjectManager $manager, $roleLabel)
    {
        $entity = new AccountUserRole();
        $entity->setLabel($roleLabel);
        $entity->setOrganization($this->getDefaultOrganization($manager));
        $entity->setSelfManaged(false);
        $this->setReference($entity->getLabel(), $entity);
        $manager->persist($entity);

        return $entity;
    }

    /**
     * @param ObjectManager $manager
     * @return Organization|null
     */
    protected function getDefaultOrganization($manager)
    {
        return $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);
    }
}
