<?php

namespace Oro\Bundle\ShoppingListBundle\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;

/**
 * Feature toggle voter that controls the availability of shopping list creation functionality.
 *
 * This voter determines whether the `shopping_list_create` feature should be enabled or disabled
 * based on the configured shopping list limit for the current user. It integrates with the feature toggle system
 * to dynamically enable or disable shopping list creation routes, operations, and UI elements
 * when the maximum number of shopping lists has been reached. This allows administrators to enforce limits
 * on the number of shopping lists per customer user, helping to manage system resources and guide user behavior.
 */
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

    #[\Override]
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
