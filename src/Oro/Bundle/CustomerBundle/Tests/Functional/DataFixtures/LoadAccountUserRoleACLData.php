<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\AccountUserRole;

class LoadAccountUserRoleACLData extends AbstractLoadACLData
{
    const ROLE_WITH_ACCOUNT_1_USER_LOCAL = 'Role with account user local';
    const ROLE_WITH_ACCOUNT_1_USER_DEEP = 'Role with account user deep';
    const ROLE_WITH_ACCOUNT_1_2_USER_LOCAL = 'Role with account 1.2 user local';
    const ROLE_WITH_ACCOUNT_2_USER_LOCAL = 'Role with account 2 user local';

    /**
     * @var array
     */
    protected static $roles = [
        self::ROLE_WITH_ACCOUNT_1_USER_DEEP => [
            'accountUser' => self::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::ROLE_WITH_ACCOUNT_1_USER_LOCAL => [
            'accountUser' => self::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL => [
            'accountUser' => self::USER_ACCOUNT_1_2_ROLE_LOCAL
        ],
        self::ROLE_WITH_ACCOUNT_2_USER_LOCAL => [
            'accountUser' => self::USER_ACCOUNT_2_ROLE_LOCAL
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);
        $this->loadAccountUserRoles($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadAccountUserRoles(ObjectManager $manager)
    {
        foreach (self::$roles as $name => $role) {
            $entity = new AccountUserRole();
            $entity->setLabel($name);
            $entity->setSelfManaged(true);

            /** @var AccountUser $accountUser */
            $accountUser = $this->getReference($role['accountUser']);
            $entity->setAccount($accountUser->getAccount());
            $accountUser->addRole($entity);

            $entity->setOrganization($accountUser->getOrganization());
            $this->setReference($entity->getLabel(), $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return AccountUserRole::class;
    }

    /**
     * @return array
     */
    protected function getSupportedRoles()
    {
        return [
            self::ROLE_LOCAL,
            self::ROLE_LOCAL_VIEW_ONLY,
            self::ROLE_DEEP,
            self::ROLE_DEEP_VIEW_ONLY,
        ];
    }
}
