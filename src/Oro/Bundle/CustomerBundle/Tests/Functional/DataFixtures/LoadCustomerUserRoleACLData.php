<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class LoadCustomerUserRoleACLData extends AbstractLoadACLData
{
    const ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL = 'Role without account user local';
    const ROLE_WITH_ACCOUNT_1_USER_LOCAL = 'Role with account user local';
    const ROLE_WITH_ACCOUNT_1_USER_DEEP = 'Role with account user deep';
    const ROLE_WITH_ACCOUNT_1_2_USER_LOCAL = 'Role with account 1.2 user local';
    const ROLE_WITH_ACCOUNT_2_USER_LOCAL = 'Role with account 2 user local';
    const ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL_CANT_DELETED = 'Role without account user local for user';
    const ROLE_WITH_ACCOUNT_1_USER_LOCAL_CANT_DELETED = 'Role with account user local for user';
    const ROLE_WITH_ACCOUNT_1_USER_DEEP_CANT_DELETED = 'Role with account user deep for user';
    const ROLE_WITH_ACCOUNT_1_2_USER_LOCAL_CANT_DELETED = 'Role with account 1.2 user local for user';
    const ROLE_WITH_ACCOUNT_2_USER_LOCAL_CANT_DELETED = 'Role with account 2 user local for user';
    /**
     * @var array
     */
    protected static $roles = [
        self::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL => [
            'accountUser' => self::USER_ACCOUNT_1_ROLE_LOCAL
        ],
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
        ]
    ];

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        parent::load($manager);
        $this->loadCustomerUserRoles($manager);
    }

    /**
     * @param ObjectManager $manager
     */
    protected function loadCustomerUserRoles(ObjectManager $manager)
    {
        foreach (self::$roles as $name => $role) {
            $entity = new CustomerUserRole();
            $entity->setLabel($name);
            $entity->setSelfManaged(true);

            /** @var AccountUser $accountUser */
            $accountUser = $this->getReference($role['accountUser']);
            if ($name !== self::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL) {
                $entity->setAccount($accountUser->getCustomer());
            }
            $entity->setOrganization($accountUser->getOrganization());
            $entityForDelete = clone $entity;

            //need to have role to get permission
            //role with users can't be deleted
            $entity->setLabel($entity->getLabel() . ' for user');
            $accountUser->addRole($entity);
            $this->setReference($entity->getLabel(), $entity);
            $this->setReference($entityForDelete->getLabel(), $entityForDelete);
            $manager->persist($entityForDelete);
            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return string
     */
    protected function getAclResourceClassName()
    {
        return CustomerUserRole::class;
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
