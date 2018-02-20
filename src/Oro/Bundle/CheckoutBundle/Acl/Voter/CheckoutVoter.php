<?php

namespace Oro\Bundle\CheckoutBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckoutVoter extends AbstractEntityVoter implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const ATTRIBUTE_CREATE = 'CHECKOUT_CREATE';

    /**
     * @var array
     */
    protected $supportedAttributes = [
        self::ATTRIBUTE_CREATE
    ];

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class)
    {
        return is_a($class, CheckoutSourceEntityInterface::class, true);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!in_array(self::ATTRIBUTE_CREATE, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        $authorizationChecker = $this->getAuthorizationChecker();
        if ($authorizationChecker->isGranted(BasicPermissionMap::PERMISSION_VIEW, $object)
            && $authorizationChecker->isGranted(sprintf(
                '%s;entity:OroCheckoutBundle:Checkout',
                BasicPermissionMap::PERMISSION_CREATE
            ))
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        return self::ACCESS_ABSTAIN;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        if (!$this->container) {
            throw new \InvalidArgumentException('ContainerInterface not injected');
        }

        return $this->container;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->getContainer()->get('security.authorization_checker');
    }
}
