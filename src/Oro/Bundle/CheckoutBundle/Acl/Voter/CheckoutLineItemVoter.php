<?php

namespace Oro\Bundle\CheckoutBundle\Acl\Voter;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\EntityClassResolverUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Checks whether it is allowed to view the CheckoutLineItem entity
 * only when the view of Checkout entity is allowed.
 */
class CheckoutLineItemVoter implements VoterInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    #[\Override]
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        if (!\is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        if (!\in_array(BasicPermission::VIEW, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        $object = EntityClassResolverUtil::getEntity($object);
        if (!$object instanceof CheckoutLineItem) {
            return self::ACCESS_ABSTAIN;
        }

        $checkout = $object->getCheckout();
        if (null === $checkout || $this->authorizationChecker->isGranted(BasicPermission::VIEW, $checkout)) {
            return self::ACCESS_GRANTED;
        }

        return self::ACCESS_DENIED;
    }
}
