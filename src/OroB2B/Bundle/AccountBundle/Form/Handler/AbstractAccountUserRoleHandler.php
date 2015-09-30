<?php

namespace OroB2B\Bundle\AccountBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountUserRoleType;

abstract class AbstractAccountUserRoleHandler extends AclRoleHandler
{
    /**
     * @var ConfigProviderInterface
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
     * @param ConfigProviderInterface $provider
     */
    public function setOwnershipConfigProvider(ConfigProviderInterface $provider)
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
            AccountUserRoleType::NAME,
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
    protected function processPrivileges(AbstractRole $role)
    {
        // product view always must be set
        $this->aclManager->setPermission(
            $this->aclManager->getSid($role),
            $this->aclManager->getOid('entity:OroB2B\Bundle\ProductBundle\Entity\Product'),
            EntityMaskBuilder::MASK_VIEW_SYSTEM
        );

        parent::processPrivileges($role);
    }

    /**
     * {@inheritDoc}
     */
    protected function getAclGroup()
    {
        return AccountUser::SECURITY_GROUP;
    }
}
