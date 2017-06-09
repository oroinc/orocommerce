<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\ConfigVoter;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class GuestShoppingListVoter implements VoterInterface
{
    const FEATURE_NAME = 'guest_shopping_list';

    /**
     * @var ConfigVoter
     */
    private $configVoter;

    /**
     * @var SecurityFacade
     */
    private $securityFacade;

    /**
     * @param ConfigVoter    $configVoter
     * @param SecurityFacade $securityFacade
     */
    public function __construct(ConfigVoter $configVoter, SecurityFacade $securityFacade)
    {
        $this->configVoter    = $configVoter;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME) {
            if (!$this->securityFacade->hasLoggedUser()) {
                return $this->configVoter->vote($feature, $scopeIdentifier);
            }

            return ConfigVoter::FEATURE_ENABLED;
        }

        return ConfigVoter::FEATURE_ABSTAIN;
    }
}
