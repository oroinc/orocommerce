<?php

namespace Oro\Bundle\CustomerBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerUserRoleRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Form\Type\CustomerUserRoleType;

use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractCustomerUserRoleHandler extends AclRoleHandler
{
    /** @var  RequestStack */
    protected $requestStack;

    /**
     * @var ConfigProvider
     */
    protected $ownershipConfigProvider;

    /**
     * @var ChainMetadataProvider
     */
    protected $chainMetadataProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Customer
     */
    protected $originalCustomer;

    /**
     * @param RequestStack $requestStack
     */
    public function setRequestStack($requestStack)
    {
        $this->requestStack = $requestStack;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @param ConfigProvider $provider
     */
    public function setOwnershipConfigProvider(ConfigProvider $provider)
    {
        $this->ownershipConfigProvider = $provider;
    }

    /**
     * @param ChainMetadataProvider $chainMetadataProvider
     */
    public function setChainMetadataProvider(ChainMetadataProvider $chainMetadataProvider)
    {
        $this->chainMetadataProvider = $chainMetadataProvider;
    }

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function setDoctrineHelper(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoleFormInstance(AbstractRole $role, array $privilegeConfig)
    {
        return $this->formFactory->create(
            CustomerUserRoleType::NAME,
            $role,
            ['privilege_config' => $privilegeConfig]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function filterPrivileges(ArrayCollection $privileges, array $rootIds)
    {
        $privileges = parent::filterPrivileges($privileges, $rootIds);

        $entityPrefix = 'entity:';

        foreach ($privileges as $key => $privilege) {
            $oid = $privilege->getIdentity()->getId();
            if (strpos($oid, $entityPrefix) === 0) {
                $className = substr($oid, strlen($entityPrefix));
                if (!$this->ownershipConfigProvider->hasConfig($className)) {
                    unset($privileges[$key]);
                }
            }
        }

        return $privileges;
    }

    /**
     * {@inheritdoc}
     */
    protected function processPrivileges(AbstractRole $role, $className = null)
    {
        $objectIdentityDescriptor = 'entity:Oro\Bundle\ProductBundle\Entity\Product';

        $extension = $this->aclManager->getExtensionSelector()->select($objectIdentityDescriptor);
        $maskBuilder = $extension->getMaskBuilder('VIEW');

        // product view always must be set
        $this->aclManager->setPermission(
            $this->aclManager->getSid($role),
            $this->aclManager->getOid($objectIdentityDescriptor),
            $maskBuilder->getMask('MASK_VIEW_SYSTEM')
        );

        parent::processPrivileges($role);
    }

    /**
     * {@inheritDoc}
     */
    protected function getAclGroup()
    {
        return CustomerUser::SECURITY_GROUP;
    }

    /**
     * @param CustomerUserRole $role
     * @return ArrayCollection[]
     */
    public function getCustomerUserRolePrivileges(CustomerUserRole $role)
    {
        $sortedPrivileges= [];
        $privileges = $this->getRolePrivileges($role);

        $this->loadPrivilegeConfigPermissions(true);

        foreach ($this->privilegeConfig as $fieldName => $config) {
            $sortedPrivileges[$fieldName] = $this->filterPrivileges($privileges, $config['types']);
            $this->applyOptions($sortedPrivileges[$fieldName], $config);
        }

        return $sortedPrivileges;
    }

    /**
     * @param CustomerUserRole $role
     * @return array
     */
    public function getCustomerUserRolePrivilegeConfig(CustomerUserRole $role)
    {
        return $this->privilegeConfig;
    }

    /**
     * @param CustomerUserRole|AbstractRole $role
     * @param array $appendUsers
     * @param array $removeUsers
     */
    protected function applyCustomerLimits(CustomerUserRole $role, array &$appendUsers, array &$removeUsers)
    {
        /** @var CustomerUserRoleRepository $roleRepository */
        $roleRepository = $this->doctrineHelper->getEntityRepository($role);

        // Role moved to another customer OR customer added
        if ($role->getId() && (
                ($this->originalCustomer !== $role->getCustomer() &&
                    $this->originalCustomer !== null && $role->getCustomer() !== null) ||
                ($this->originalCustomer === null && $role->getCustomer() !== null)
            )
        ) {
            // Remove assigned users
            $assignedUsers = $roleRepository->getAssignedUsers($role);

            $removeUsers = array_replace(
                $removeUsers,
                array_filter(
                    $assignedUsers,
                    function (CustomerUser $customerUser) use ($role) {
                        return $customerUser->getCustomer() !== $role->getCustomer();
                    }
                )
            );

            $appendNewUsers = array_diff($appendUsers, $removeUsers);
            $removeNewUsers = array_diff($removeUsers, $appendUsers);

            $removeUsers = $removeNewUsers;
            $appendUsers = $appendNewUsers;
        }

        if ($role->getCustomer()) {
            // Security check
            $appendUsers = array_filter(
                $appendUsers,
                function (CustomerUser $user) use ($role) {
                    return $user->getCustomer() === $role->getCustomer();
                }
            );
        }
    }
}
