<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;

class GuestShoppingListVoter implements VoterInterface
{
    const FEATURE_NAME = 'guest_shopping_list';

    /**
     * @var VoterInterface
     */
    private $configVoter;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @param VoterInterface        $configVoter
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(VoterInterface $configVoter, TokenStorageInterface $tokenStorage)
    {
        $this->configVoter  = $configVoter;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME) {
            if ($this->tokenStorage->getToken() instanceof AnonymousCustomerUserToken) {
                return $this->configVoter->vote($feature, $scopeIdentifier);
            }

            return VoterInterface::FEATURE_ENABLED;
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
