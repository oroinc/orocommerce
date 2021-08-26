<?php

namespace Oro\Bundle\ProductBundle\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

/**
 * Votes for simple_variations_view_restriction feature to be enabled
 * when oro_product.display_simple_variations is set to hide_completely.
 */
class SimpleVariationsRestrictionFeatureVoter implements VoterInterface
{
    private const FEATURE_NAME = 'simple_variations_view_restriction';

    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME) {
            $configValue = $this->configManager->get(
                sprintf(
                    '%s.%s',
                    Configuration::ROOT_NODE,
                    Configuration::DISPLAY_SIMPLE_VARIATIONS
                )
            );
            if ($configValue === Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY) {
                return self::FEATURE_ENABLED;
            }

            return self::FEATURE_DISABLED;
        }

        return self::FEATURE_ABSTAIN;
    }
}
