<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class IsWorkflowStartFromShoppingListAllowed
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param FeatureChecker        $featureChecker
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(FeatureChecker $featureChecker, TokenStorageInterface $tokenStorage)
    {
        $this->featureChecker = $featureChecker;
        $this->tokenStorage   = $tokenStorage;
    }

    /**
     * Allows button for logged user
     * @return bool
     */
    public function isAllowedForLogged()
    {
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            return false;
        }

        return true;
    }

    /**
     * Allows button for Guest user and only if Guest checkout feature is enabled
     * @return bool
     */
    public function isAllowedForGuest()
    {
        return $this->isAllowedForAny(false);
    }

    /**
     * Allows button if User logged or feature enabled for Guest
     * @param bool $allowedByDefault
     * @return bool
     */
    public function isAllowedForAny($allowedByDefault = true)
    {
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            return $this->featureChecker->isFeatureEnabled('guest_checkout');
        }

        return $allowedByDefault;
    }
}
