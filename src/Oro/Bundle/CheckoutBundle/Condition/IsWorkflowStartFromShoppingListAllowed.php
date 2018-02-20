<?php

namespace Oro\Bundle\CheckoutBundle\Condition;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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
     * @return bool
     */
    public function isAllowed()
    {
        $isAllowed = true;
        if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
            $isAllowed = $this->featureChecker->isFeatureEnabled('guest_checkout');
        }

        return $isAllowed;
    }
}
