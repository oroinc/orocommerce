<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

class ShoppingListCreateVoter implements VoterInterface
{
    const FEATURE_NAME = 'shopping_list_create';

    /**
     * @var ShoppingListLimitManager
     */
    private $shoppingListLimitManager;

    public function __construct(ShoppingListLimitManager $shoppingListLimitManager)
    {
        $this->shoppingListLimitManager  = $shoppingListLimitManager;
    }

    /**
     * {@inheritDoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME) {
            return $this->shoppingListLimitManager->isCreateEnabled() ?
                VoterInterface::FEATURE_ENABLED :
                VoterInterface::FEATURE_DISABLED;
        }

        return VoterInterface::FEATURE_ABSTAIN;
    }
}
