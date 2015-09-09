<?php

namespace OroB2B\Bundle\AccountBundle\Security;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserProvider
{
    /**
     * @var SecurityFacade
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
     * @param SecurityFacade $securityFacade
     * @param AclManager $aclManager
     */
    public function __construct(SecurityFacade $securityFacade, AclManager $aclManager)
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

        $descriptor = sprintf('entity:%s', ClassUtils::getRealClass($class));
        $oid = $this->aclManager->getOid($descriptor);
        $rootOid = $this->aclManager->getRootOid($oid);

        return $this->isGrantedOidMask($oid, $class, $mask) || $this->isGrantedOidMask($rootOid, $class, $mask);
    }

    /**
     * @param ObjectIdentity $oid
     * @param string $class
     * @param int $requiredMask
     * @return bool
     *
     * @see \Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy::isAceApplicable
     */
    private function isGrantedOidMask(ObjectIdentity $oid, $class, $requiredMask)
    {
        if (null === ($loggedUser = $this->getLoggedUser())) {
            return false;
        }

        $extension = $this->aclManager->getExtensionSelector()->select($oid);
        foreach ($loggedUser->getRoles() as $role) {
            $sid = $this->aclManager->getSid($role);
            $aces = $this->aclManager->getAces($sid, $oid);
            foreach ($aces as $ace) {
                if ($ace->getAcl()->getObjectIdentity()->getIdentifier() !== $extension->getExtensionKey()) {
                    continue;
                }

                $aceMask = $ace->getMask();
                if ($oid->getType() === ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                    $aceMask = $extension->adaptRootMask($aceMask, new $class);
                }

                if ($extension->getServiceBits($requiredMask) !== $extension->getServiceBits($aceMask)) {
                    continue;
                }

                $requiredMask = $extension->removeServiceBits($requiredMask);
                $aceMask = $extension->removeServiceBits($aceMask);
                $strategy = $ace->getStrategy();
                $decision = $this->decide($strategy, $requiredMask, $aceMask);
                if (null !== $decision) {
                    return $decision;
                }
            }
        }

        return false;
    }

    /**
     * @param string $strategy
     * @param int $requiredMask
     * @param int $aceMask
     * @return bool|null
     */
    private function decide($strategy, $requiredMask, $aceMask)
    {
        if (PermissionGrantingStrategy::ALL === $strategy) {
            return $requiredMask === ($aceMask & $requiredMask);
        } elseif (PermissionGrantingStrategy::ANY === $strategy) {
            return 0 !== ($aceMask & $requiredMask);
        } elseif (PermissionGrantingStrategy::EQUAL === $strategy) {
            return $requiredMask === $aceMask;
        }

        return null;
    }
}
