<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;

class LoadCustomerUserRoleACLData extends AbstractLoadACLData
{
    const ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL = 'Role without customer user local';
    const ROLE_WITH_ACCOUNT_1_USER_LOCAL = 'Role with customer user local';
    const ROLE_WITH_ACCOUNT_1_USER_DEEP = 'Role with customer user deep';
    const ROLE_WITH_ACCOUNT_1_2_USER_LOCAL = 'Role with customer 1.2 user local';
    const ROLE_WITH_ACCOUNT_2_USER_LOCAL = 'Role with customer 2 user local';
    const ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL_CANT_DELETED = 'Role without customer user local for user';
    const ROLE_WITH_ACCOUNT_1_USER_LOCAL_CANT_DELETED = 'Role with customer user local for user';
    const ROLE_WITH_ACCOUNT_1_USER_DEEP_CANT_DELETED = 'Role with customer user deep for user';
    const ROLE_WITH_ACCOUNT_1_2_USER_LOCAL_CANT_DELETED = 'Role with customer 1.2 user local for user';
    const ROLE_WITH_ACCOUNT_2_USER_LOCAL_CANT_DELETED = 'Role with customer 2 user local for user';
    /**
     * @var array
     */
    protected static $roles = [
        self::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL => [
            'customerUser' => self::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::ROLE_WITH_ACCOUNT_1_USER_DEEP => [
            'customerUser' => self::USER_ACCOUNT_1_ROLE_DEEP
        ],
        self::ROLE_WITH_ACCOUNT_1_USER_LOCAL => [
            'customerUser' => self::USER_ACCOUNT_1_ROLE_LOCAL
        ],
        self::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL => [
            'customerUser' => self::USER_ACCOUNT_1_2_ROLE_LOCAL
        ],
        self::ROLE_WITH_ACCOUNT_2_USER_LOCAL => [
            'customerUser' => self::USER_ACCOUNT_2_ROLE_LOCAL
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

            /** @var CustomerUser $customerUser */
            $customerUser = $this->getReference($role['customerUser']);
            if ($name !== self::ROLE_WITHOUT_ACCOUNT_1_USER_LOCAL) {
                $entity->setCustomer($customerUser->getCustomer());
            }
            $entity->setOrganization($customerUser->getOrganization());
            $entityForDelete = clone $entity;

            //need to have role to get permission
            //role with users can't be deleted
            $entity->setLabel($entity->getLabel() . ' for user');
            $customerUser->addRole($entity);
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
