<?php

namespace Oro\Bundle\CheckoutBundle\Api;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The utility class that can be used to check whether the current security context
 * represents a visitor and the checkout feature is enabled for visitors.
 */
class GuestCheckoutChecker
{
    private TokenStorageInterface $tokenStorage;
    private FeatureChecker $featureChecker;

    public function __construct(TokenStorageInterface $tokenStorage, FeatureChecker $featureChecker)
    {
        $this->tokenStorage = $tokenStorage;
        $this->featureChecker = $featureChecker;
    }

    /**
     * Checks whether the current security context represents a visitor
     * and the checkout feature is enabled for visitors.
     */
    public function isGuestWithEnabledCheckout(): bool
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken || null === $token->getVisitor()) {
            return false;
        }

        return $this->featureChecker->isFeatureEnabled('guest_checkout');
    }

    /**
     * Returns a visitor from the current security context.
     */
    public function getVisitor(): CustomerVisitor
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            throw new \LogicException(sprintf(
                'Unexpected token type %s, expected %s.',
                null !== $token ? \get_class($token) : 'NULL',
                AnonymousCustomerUserToken::class
            ));
        }
        $visitor = $token->getVisitor();
        if (null === $visitor) {
            throw new \LogicException('The token does not have a visitor.');
        }

        return $visitor;
    }
}
