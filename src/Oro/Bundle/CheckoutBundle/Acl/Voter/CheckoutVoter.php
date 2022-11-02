<?php

namespace Oro\Bundle\CheckoutBundle\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\EntityClassResolverUtil;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Checks whether it is allowed to create the Checkout entity
 * from an entity that implements CheckoutSourceEntityInterface.
 */
class CheckoutVoter implements VoterInterface
{
    private const ATTRIBUTE_CREATE = 'CHECKOUT_CREATE';

    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!\is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!\in_array(self::ATTRIBUTE_CREATE, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!EntityClassResolverUtil::isEntityClass($object, CheckoutSourceEntityInterface::class)) {
            return self::ACCESS_ABSTAIN;
        }

        if ($this->authorizationChecker->isGranted(BasicPermission::VIEW, $object)
            && $this->authorizationChecker->isGranted(BasicPermission::CREATE, 'entity:' . Checkout::class)
        ) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}
