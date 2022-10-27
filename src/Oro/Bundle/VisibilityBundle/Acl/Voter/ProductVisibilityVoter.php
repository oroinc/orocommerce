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
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::VIEW];

    private FrontendHelper $frontendHelper;
    private ContainerInterface $container;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        FrontendHelper $frontendHelper,
        ContainerInterface $container
    ) {
        parent::__construct($doctrineHelper);
        $this->frontendHelper = $frontendHelper;
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_visibility.provider.resolved_product_visibility_provider' => ResolvedProductVisibilityProvider::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if ($this->frontendHelper->isFrontendRequest()) {
            return parent::vote($token, $object, $attributes);
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute): int
    {
        return $this->getResolvedProductVisibilityProvider()->isVisible($identifier)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }

    private function getResolvedProductVisibilityProvider(): ResolvedProductVisibilityProvider
    {
        return $this->container->get('oro_visibility.provider.resolved_product_visibility_provider');
    }
}
