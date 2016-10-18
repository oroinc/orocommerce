<?php

namespace Oro\Bundle\CustomerBundle\Event;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\SecurityContextInterface;

class RecordOwnerDataListener
{
    const OWNER_TYPE_USER = 'FRONTEND_USER';
    const OWNER_TYPE_ACCOUNT = 'FRONTEND_ACCOUNT';

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
        if (!($user instanceof AccountUser)) {
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
                && in_array($frontendOwnerType, [self::OWNER_TYPE_USER, self::OWNER_TYPE_ACCOUNT], true)
                && !$accessor->getValue($entity, $ownerFieldName)
            ) {
                $owner = null;
                if ($frontendOwnerType === self::OWNER_TYPE_USER) {
                    $owner = $user;
                }
                if ($frontendOwnerType === self::OWNER_TYPE_ACCOUNT) {
                    $owner = $user->getAccount();
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
