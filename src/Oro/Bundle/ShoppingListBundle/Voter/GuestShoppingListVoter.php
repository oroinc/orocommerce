<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class GuestShoppingListVoter implements VoterInterface
{
    const FEATURE_NAME = 'guest_shopping_list';

    /**
     * @var ConfigVoter
     */
    private $configVoter;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @param ConfigVoter    $configVoter
     * @param TokenStorage   $tokenStorage
     */
    public function __construct(ConfigVoter $configVoter, TokenStorage $tokenStorage)
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
            if ($this->tokenStorage->getToken() instanceof AnonymousToken) {
                return $this->configVoter->vote($feature, $scopeIdentifier);
            }

            return ConfigVoter::FEATURE_ENABLED;
        }

        return ConfigVoter::FEATURE_ABSTAIN;
    }
}
