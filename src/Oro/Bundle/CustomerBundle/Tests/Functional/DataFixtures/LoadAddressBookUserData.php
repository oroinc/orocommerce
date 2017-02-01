<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadAddressBookUserData extends AbstractLoadCustomerUserFixture
{
    const USER1 = 'user1';
    const USER2 = 'user2';
    const USER3 = 'user3';
    const USER4 = 'user4';
    const USER5 = 'user5';

    /** VIEW ACCOUNT ADDRESS */
    const ROLE1_V_AC_AD = 'role-V_ac_addr';
    /** VIEW ACCOUNT USER ADDRESS */
    const ROLE2_V_ACU_AD = 'role-V_acu_addr';
    /** VIEW ACCOUNT ADDRESS AND VIEW ACCOUNT USER ADDRESS */
    const ROLE3_V_AC_AD_V_ACU_AD = 'role-V_ac_addr-V-acu_addr';
    const ROLE4_NONE = 'role_none';
    /** VIEW/CREATE/EDIT/DELETE ACCOUNT ADDRESS and ACCOUNT USER ADDRESS */
    const ROLE5_VCED_AC_AD_VCED_AU_AD = 'role-VCED_ac_addr_VCED_au_addr';
    const ROLE6_VC_AC_AD = 'role-VC_ac_addr';
    const ROLE7_VC_AU_AD = 'role-VC_acu_addr';

    const ACCOUNT1 = 'customer1';

    const ACCOUNT1_USER1 = 'customer1-user1@example.com';
    const ACCOUNT1_USER2 = 'customer1-user2@example.com';
    const ACCOUNT1_USER3 = 'customer1-user3@example.com';
    const ACCOUNT1_USER4 = 'customer1-user4@example.com';
    const ACCOUNT1_USER5 = 'customer1-user5@example.com';
    const ACCOUNT1_USER6 = 'customer1-user6@example.com';
    const ACCOUNT1_USER7 = 'customer1-user7@example.com';

    /**
     * @var array
     */
    protected $roles = [
        self::ROLE1_V_AC_AD => [
            [
                'class' => 'oro_customer.entity.customer_address.class',
                'acls' => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE2_V_ACU_AD => [
            [
                'class' => 'oro_customer.entity.customer_user_address.class',
                'acls' => ['VIEW_BASIC'],
            ],
        ],
        self::ROLE3_V_AC_AD_V_ACU_AD => [
            [
                'class' => 'oro_customer.entity.customer_user_address.class',
                'acls' => ['VIEW_BASIC'],
            ],
            [
                'class' => 'oro_customer.entity.customer_address.class',
                'acls' => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE4_NONE => [],
        self::ROLE5_VCED_AC_AD_VCED_AU_AD => [
            [
                'class' => 'oro_customer.entity.customer_user_address.class',
                'acls' => ['VIEW_BASIC', 'EDIT_BASIC', 'CREATE_BASIC'],
            ],
            [
                'class' => 'oro_customer.entity.customer_address.class',
                'acls' => ['VIEW_LOCAL'],
            ],
        ],
        self::ROLE6_VC_AC_AD => [
            [
                'class' => 'oro_customer.entity.customer_address.class',
                'acls' => ['VIEW_LOCAL', 'CREATE_LOCAL'],
            ],
        ],
        self::ROLE7_VC_AU_AD => [
            [
                'class' => 'oro_customer.entity.customer_user_address.class',
                'acls' => ['VIEW_BASIC', 'CREATE_BASIC'],
            ],
        ]
    ];

    /**
     * @var array
     */
    protected $customers = [
        [
            'name' => self::ACCOUNT1,
        ],
    ];

    /**
     * @var array
     */
    protected $customerUsers = [
        [
            'email' => self::ACCOUNT1_USER1,
            'firstname' => 'User1FN',
            'lastname' => 'User1LN',
            'password' => self::ACCOUNT1_USER1,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE1_V_AC_AD,
        ],
        [
            'email' => self::ACCOUNT1_USER2,
            'firstname' => 'User2FN',
            'lastname' => 'User2LN',
            'password' => self::ACCOUNT1_USER2,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE2_V_ACU_AD,
        ],
        [
            'email' => self::ACCOUNT1_USER3,
            'firstname' => 'User3FN',
            'lastname' => 'User3LN',
            'password' => self::ACCOUNT1_USER3,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE3_V_AC_AD_V_ACU_AD,
        ],
        [
            'email' => self::ACCOUNT1_USER4,
            'firstname' => 'User3FN',
            'lastname' => 'User3LN',
            'password' => self::ACCOUNT1_USER4,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE4_NONE,
        ],
        [
            'email' => self::ACCOUNT1_USER5,
            'firstname' => 'User4FN',
            'lastname' => 'User4LN',
            'password' => self::ACCOUNT1_USER5,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE5_VCED_AC_AD_VCED_AU_AD,
        ],
        [
            'email' => self::ACCOUNT1_USER6,
            'firstname' => 'User4FN',
            'lastname' => 'User4LN',
            'password' => self::ACCOUNT1_USER6,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE6_VC_AC_AD
        ],
        [
            'email' => self::ACCOUNT1_USER7,
            'firstname' => 'User4FN',
            'lastname' => 'User4LN',
            'password' => self::ACCOUNT1_USER7,
            'customer' => self::ACCOUNT1,
            'role' => self::ROLE7_VC_AU_AD,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadAdminUserData::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomers()
    {
        return $this->customers;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomerUsers()
    {
        return $this->customerUsers;
    }
}
