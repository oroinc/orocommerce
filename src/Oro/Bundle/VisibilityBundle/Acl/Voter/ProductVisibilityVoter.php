<?php

namespace Oro\Bundle\VisibilityBundle\Acl\Voter;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Prevents direct access to the products with disabled visibility.
 */
class ProductVisibilityVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    protected $supportedAttributes = [BasicPermission::VIEW];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        private readonly FrontendHelper $frontendHelper,
        private readonly ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ResolvedProductVisibilityProvider::class
        ];
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    #[\Override]
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        return $this->getResolvedProductVisibilityProvider()->isVisible($identifier)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function getResolvedProductVisibilityProvider(): ResolvedProductVisibilityProvider
    {
        return $this->container->get(ResolvedProductVisibilityProvider::class);
    }
}
