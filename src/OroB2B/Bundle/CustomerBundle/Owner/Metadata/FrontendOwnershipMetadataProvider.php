<?php

namespace OroB2B\Bundle\CustomerBundle\Owner\Metadata;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;

class FrontendOwnershipMetadataProvider extends AbstractMetadataProvider
{
    /**
     * @var string
     */
    protected $localLevelClass;

    /**
     * @var string
     */
    protected $basicLevelClass;

    /**
     * @var FrontendOwnershipMetadataProvider
     */
    private $noOwnershipMetadata;

    /**
     * {@inheritDoc}
     */
    protected function setAccessLevelClasses(array $owningEntityNames, EntityClassResolver $entityClassResolver = null)
    {
        if (!isset($owningEntityNames['local_level'], $owningEntityNames['basic_level'])) {
            throw new \InvalidArgumentException(
                'Array parameter $owningEntityNames must contains `local_level` and `basic_level` keys'
            );
        }

        $this->localLevelClass = $this->getEntityClassResolver()->getEntityClass($owningEntityNames['local_level']);
        $this->basicLevelClass = $this->getEntityClassResolver()->getEntityClass($owningEntityNames['basic_level']);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNoOwnershipMetadata()
    {
        if (!$this->noOwnershipMetadata) {
            $this->noOwnershipMetadata = new FrontendOwnershipMetadata();
        }

        return $this->noOwnershipMetadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getSystemLevelClass()
    {
        throw new \BadMethodCallException('Method getSystemLevelClass() unsupported.');
    }

    /**
     * {@inheritDoc}
     */
    public function getGlobalLevelClass()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalLevelClass($deep = false)
    {
        return $this->localLevelClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getBasicLevelClass()
    {
        return $this->basicLevelClass;
    }

    /**
     * {@inheritDoc}
     */
    public function supports()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser() instanceof AccountUser;
    }

    /**
     * {@inheritDoc}
     */
    protected function getOwnershipMetadata(ConfigInterface $config)
    {
        $ownerType              = $config->get('frontend_owner_type');
        $ownerFieldName         = $config->get('frontend_owner_field_name');
        $ownerColumnName        = $config->get('frontend_owner_column_name');
        $organizationFieldName  = $config->get('organization_field_name');
        $organizationColumnName = $config->get('organization_column_name');

        return new FrontendOwnershipMetadata(
            $ownerType,
            $ownerFieldName,
            $ownerColumnName,
            $organizationFieldName,
            $organizationColumnName
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxAccessLevel($accessLevel, $className = null)
    {
        return $accessLevel;
    }
}
