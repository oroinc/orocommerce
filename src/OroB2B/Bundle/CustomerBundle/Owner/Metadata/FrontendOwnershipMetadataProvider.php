<?php

namespace OroB2B\Bundle\CustomerBundle\Owner\Metadata;

use Doctrine\Common\Cache\CacheProvider;

use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\AbstractMetadataProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

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
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param array $owningEntityNames
     * @param SecurityFacade $securityFacade
     * @param ConfigProvider $configProvider
     * @param CacheProvider|null $cache
     */
    public function __construct(
        array $owningEntityNames,
        SecurityFacade $securityFacade,
        ConfigProvider $configProvider,
        CacheProvider $cache = null
    ) {
        parent::__construct($owningEntityNames, $configProvider, null, $cache);

        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritDoc}
     */
    protected function setAccessLevelClasses(array $owningEntityNames, EntityClassResolver $entityClassResolver = null)
    {
        $this->localLevelClass = $owningEntityNames['local_level'];
        $this->basicLevelClass = $owningEntityNames['basic_level'];
    }

    /**
     * {@inheritDoc}
     */
    protected function createNoOwnershipMetadata()
    {
        $this->noOwnershipMetadata = new FrontendOwnershipMetadata();
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
        throw new \BadMethodCallException('Method getGlobalLevelClass() unsupported.');
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
        return $this->securityFacade && $this->securityFacade->getLoggedUser() instanceof AccountUser;
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
}
