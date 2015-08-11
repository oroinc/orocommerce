<?php

namespace OroB2B\Bundle\AccountBundle;

use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\SecurityFacade as BaseSecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class SecurityFacade
{
    /**
     * @var BaseSecurityFacade
     */
    protected $securityFacade;

    /**
     * @var AclManager
     */
    protected $aclManager;

    /**
     * @var string
     */
    protected $accountUserClass;

    /**
     * @param BaseSecurityFacade $securityFacade
     * @param AclManager $aclManager
     */
    public function __construct(BaseSecurityFacade $securityFacade, AclManager $aclManager)
    {
        $this->securityFacade = $securityFacade;
        $this->aclManager = $aclManager;
    }

    /**
     * @param string $class
     */
    public function setAccountUserClass($class)
    {
        $this->accountUserClass = $class;
    }

    /**
     * @return AccountUser|null
     */
    public function getLoggedUser()
    {
        return $this->securityFacade->getLoggedUser();
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewBasic($class)
    {
        return $this->isGrantedEntityMask($class, EntityMaskBuilder::MASK_VIEW_BASIC);
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewLocal($class)
    {
        return $this->isGrantedEntityMask($class, EntityMaskBuilder::MASK_VIEW_LOCAL);
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewAccountUser($class)
    {
        $descriptor = sprintf('entity:%s@%s', AccountUser::SECURITY_GROUP, $this->accountUserClass);
        if (!$this->securityFacade->isGranted(BasicPermissionMap::PERMISSION_VIEW, $descriptor)) {
            return false;
        }

        if (!$this->isGrantedViewLocal($class)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $class
     * @param int $mask
     * @return boolean
     */
    protected function isGrantedEntityMask($class, $mask)
    {
        if (!$class) {
            return false;
        }

        if (null === ($loggedUser = $this->getLoggedUser())) {
            return false;
        }

        $descriptor = sprintf('entity:%s', ClassUtils::getRealClass($class));
        $oid = $this->aclManager->getOid($descriptor);

        foreach ($loggedUser->getRoles() as $role) {
            $aces = $this->aclManager->getAces($this->aclManager->getSid($role), $oid);
            foreach ($aces as $ace) {
                if ($ace->getMask() & $mask) {
                    return true;
                }
            }
        }

        return false;
    }
}
