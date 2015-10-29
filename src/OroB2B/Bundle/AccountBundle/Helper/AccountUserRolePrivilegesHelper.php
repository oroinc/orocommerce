<?php

namespace OroB2B\Bundle\AccountBundle\Helper;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;
use OroB2B\Bundle\AccountBundle\Form\Handler\AbstractAccountUserRoleHandler;

/**
 * Helper class used to get role privileges for view pages.
 * Uses AbstractAccountUserRoleHandler not to reveal internal role handling outside
 */
class AccountUserRolePrivilegesHelper
{
    /**
     * @var AbstractAccountUserRoleHandler
     */
    protected $accountUserRoleHandler;

    /**
     * @param AbstractAccountUserRoleHandler $accountUserRoleHandler
     */
    public function __construct(AbstractAccountUserRoleHandler $accountUserRoleHandler)
    {
        $this->accountUserRoleHandler = $accountUserRoleHandler;
    }

    /**
     * @param AccountUserRole $accountUserRole
     * @return array
     */
    public function collect(AccountUserRole $accountUserRole)
    {
        return [
            'data' => $this->accountUserRoleHandler->getAccountUserRolePrivileges($accountUserRole),
            'privilegesConfig' => $this->accountUserRoleHandler->getAccountUserRolePrivilegeConfig($accountUserRole),
            'accessLevelNames' => AccessLevel::$allAccessLevelNames,
        ];
    }
}
