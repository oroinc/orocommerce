<?php

namespace Oro\Bundle\CustomerBundle\Security;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Acl\Util\ClassUtils;

use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class CustomerUserProvider
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
    protected $customerUserClass;

    /**
     * @var array|EntityMaskBuilder[]
     */
    protected $maskBuilders = [];

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
    public function setCustomerUserClass($class)
    {
        $this->customerUserClass = $class;
    }

    /**
     * @return CustomerUser|null
     */
    public function getLoggedUser()
    {
        $user = $this->securityFacade->getLoggedUser();
        if ($user instanceof CustomerUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewBasic($class)
    {
        return $this->isGranted($class, 'VIEW', 'BASIC');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewLocal($class)
    {
        return $this->isGranted($class, 'VIEW', 'LOCAL');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewDeep($class)
    {
        return $this->isGranted($class, 'VIEW', 'DEEP');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewSystem($class)
    {
        return $this->isGranted($class, 'VIEW', 'SYSTEM');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedEditBasic($class)
    {
        return $this->isGranted($class, 'EDIT', 'BASIC');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedEditLocal($class)
    {
        return $this->isGranted($class, 'EDIT', 'LOCAL');
    }

    /**
     * @param string $class
     * @return boolean
     */
    public function isGrantedViewCustomerUser($class)
    {
        $descriptor = sprintf('entity:%s@%s', CustomerUser::SECURITY_GROUP, $this->customerUserClass);
        if (!$this->securityFacade->isGranted(BasicPermissionMap::PERMISSION_VIEW, $descriptor)) {
            return false;
        }

        if ($this->isGrantedViewLocal($class) ||
            $this->isGrantedViewDeep($class) ||
            $this->isGrantedViewSystem($class)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $class
     * @param string $permission
     * @param string $level
     * @return bool
     */
    private function isGranted($class, $permission, $level)
    {
        return $this->isGrantedEntityMask(
            $class,
            $this->getMaskBuilderForPermission($permission)->getMask('MASK_' . $permission . '_' . $level)
        );
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

        return $this->isGrantedOidMask($oid, $class, $mask);
    }

    /**
     * @param ObjectIdentity $oid
     * @param string $class
     * @param int $requiredMask
     * @return bool
     *
     * @see \Oro\Bundle\SecurityBundle\Acl\Domain\PermissionGrantingStrategy::isAceApplicable
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
            if (!$aces && $oid->getType() !== ObjectIdentityFactory::ROOT_IDENTITY_TYPE) {
                $rootOid = $this->aclManager->getRootOid($oid);

                return $this->isGrantedOidMask(
                    $rootOid,
                    $class,
                    $this->getMaskBuilderForMask($requiredMask)->getMask('GROUP_SYSTEM')
                );
            }

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
                if (PermissionGrantingStrategy::ALL === $strategy) {
                    return $requiredMask === ($aceMask & $requiredMask);
                } elseif (PermissionGrantingStrategy::ANY === $strategy) {
                    return 0 !== ($aceMask & $requiredMask);
                } elseif (PermissionGrantingStrategy::EQUAL === $strategy) {
                    return $requiredMask === $aceMask;
                }
            }
        }

        return false;
    }

    /**
     * @return EntityAclExtension
     */
    protected function getEntityAclExtension()
    {
        return $this->aclManager->getExtensionSelector()->select('entity:(root)');
    }

    /**
     * @param string $permission
     * @return EntityMaskBuilder
     */
    protected function getMaskBuilderForPermission($permission)
    {
        if (!array_key_exists($permission, $this->maskBuilders)) {
            $this->maskBuilders[$permission] = $this->getEntityAclExtension()->getMaskBuilder($permission);
        }

        return $this->maskBuilders[$permission];
    }

    /**
     * @param int $requiredMask
     * @return EntityMaskBuilder
     */
    protected function getMaskBuilderForMask($requiredMask)
    {
        $extension = $this->getEntityAclExtension();

        $serviceBits = $extension->getServiceBits($requiredMask);

        /** @var EntityMaskBuilder[] $maskBuilders */
        $maskBuilders = $extension->getAllMaskBuilders();

        foreach ($maskBuilders as $maskBuilder) {
            if ($serviceBits === $maskBuilder->getIdentity()) {
                return $maskBuilder;
            }
        }

        throw new \RuntimeException('MaskBuilder could not found.');
    }
}
