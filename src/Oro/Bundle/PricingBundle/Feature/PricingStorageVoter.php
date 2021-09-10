<?php

namespace Oro\Bundle\PricingBundle\Feature;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Disable oro_price_lists_combined/oro_price_lists_flat feature base don configured storage.
 */
class PricingStorageVoter implements VoterInterface
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature !== 'oro_price_lists_flat' && $feature !== 'oro_price_lists_combined') {
            return VoterInterface::FEATURE_ABSTAIN;
        }

        $configuredStorage = $this->configManager->get('oro_pricing.price_storage');
        if ($feature === 'oro_price_lists_' . $configuredStorage) {
            return VoterInterface::FEATURE_ENABLED;
        }

        return VoterInterface::FEATURE_DISABLED;
    }
}
