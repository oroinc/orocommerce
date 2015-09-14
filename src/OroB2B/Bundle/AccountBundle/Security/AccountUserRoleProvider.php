<?php

namespace OroB2B\Bundle\AccountBundle\Security;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserRole;

class AccountUserRoleProvider
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $accountUserRoleClass;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $class
     */
    public function setAccountUserRoleClass($class)
    {
        $this->accountUserRoleClass = $class;
    }

    /**
     * @return AccountUser|null
     */
    public function getLoggedUser()
    {
        return $this->securityFacade->getLoggedUser();
    }

    /**
     * @return boolean
     */
    public function isGrantedUpdateAccountUserRole()
    {
        return $this->isGrantedAccountUserRole(BasicPermissionMap::PERMISSION_EDIT);
    }

    /**
     * @return boolean
     */
    public function isGrantedViewAccountUserRole()
    {
        return $this->isGrantedAccountUserRole(BasicPermissionMap::PERMISSION_VIEW);
    }

    /**
     * @param $permissionMap
     * @return bool
     */
    protected function isGrantedAccountUserRole($permissionMap)
    {
        $descriptor = sprintf('entity:%s@%s', AccountUser::SECURITY_GROUP, $this->accountUserRoleClass);
        if (!$this->securityFacade->isGranted($permissionMap, $descriptor)) {
            return false;
        }

        return true;
    }
}
