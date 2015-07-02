<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\ChainMetadataProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\MetadataProviderInterface;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Form\Handler\AclRoleHandler;

use OroB2B\Bundle\CustomerBundle\Form\Type\AccountUserRoleType;

class AccountUserRoleHandler extends AclRoleHandler
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
     * @var MetadataProviderInterface
     */
    protected $emulatedMetadataProvider;

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
     * @param MetadataProviderInterface $emulatedMetadataProvider
     */
    public function setEmulatedMetadataProvider(MetadataProviderInterface $emulatedMetadataProvider)
    {
        $this->emulatedMetadataProvider = $emulatedMetadataProvider;
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
                if (!$this->hasFrontendOwnership($className)) {
                    unset($privileges[$key]);
                }
            }
        }

        return $privileges;
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function hasFrontendOwnership($className)
    {
        if ($this->ownershipConfigProvider->hasConfig($className)) {
            $config = $this->ownershipConfigProvider->getConfig($className);
            if ($config->has('frontend_owner_type')) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRolePrivileges(AbstractRole $role)
    {
        $this->startFrontendProviderEmulation();
        $privileges = parent::getRolePrivileges($role);
        $this->stopFrontendProviderEmulation();

        return $privileges;
    }

    /**
     * {@inheritdoc}
     */
    protected function processPrivileges(AbstractRole $role)
    {
        $this->startFrontendProviderEmulation();
        parent::processPrivileges($role);
        $this->stopFrontendProviderEmulation();
    }

    protected function startFrontendProviderEmulation()
    {
        if ($this->chainMetadataProvider && $this->emulatedMetadataProvider) {
            $this->chainMetadataProvider->startProviderEmulation($this->emulatedMetadataProvider);
        }
    }

    protected function stopFrontendProviderEmulation()
    {
        if ($this->chainMetadataProvider) {
            $this->chainMetadataProvider->stopProviderEmulation();
        }
    }
}
