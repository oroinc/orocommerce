<?php

namespace Oro\Bundle\AccountBundle\Event;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

class RecordOwnerDataListener
{
    const OWNER_TYPE_USER = 'FRONTEND_USER';

    /** @var ServiceLink */
    protected $securityContextLink;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ServiceLink    $securityContextLink
     * @param ConfigProvider $configProvider
     */
    public function __construct(ServiceLink $securityContextLink, ConfigProvider $configProvider)
    {
        $this->securityContextLink = $securityContextLink;
        $this->configProvider  = $configProvider;
    }

    /**
     * Handle prePersist.
     *
     * @param LifecycleEventArgs $args
     * @throws \LogicException when getOwner method isn't implemented for entity with ownership type
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $token = $this->getSecurityContext()->getToken();
        if (!$token) {
            return;
        }
        $user = $token->getUser();
        if (!$user) {
            return;
        }
        $entity    = $args->getEntity();
        $className = ClassUtils::getClass($entity);
        if ($this->configProvider->hasConfig($className)) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $config = $this->configProvider->getConfig($className);
            $frontendOwnerType = $config->get('frontend_owner_type');
            $ownerFieldName = $config->get('frontend_owner_field_name');
            // set default owner for organization and user owning entities
            if ($frontendOwnerType
                && $frontendOwnerType == self::OWNER_TYPE_USER
                && !$accessor->getValue($entity, $ownerFieldName)
            ) {
                $owner = null;
                if ($user instanceof AccountUser) {
                    $owner = $user;
                }
                $accessor->setValue(
                    $entity,
                    $ownerFieldName,
                    $owner
                );
            }
        }
    }

    /**
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->securityContextLink->getService();
    }
}
