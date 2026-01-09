<?php

namespace Oro\Bundle\WebCatalogBundle\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

/**
 * Determines whether the frontend master catalog feature should be enabled or disabled.
 *
 * This voter controls the availability of the master catalog feature on the storefront based on whether
 * a web catalog is configured. When a web catalog is assigned in the system configuration, the master catalog feature
 * is disabled (as the web catalog takes precedence). When no web catalog is configured,
 * the master catalog feature is enabled, allowing the default category-based navigation.
 */
class FeatureVoter implements VoterInterface
{
    public const FEATURE_NAME = 'frontend_master_catalog';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME) {
            if ($this->configManager->get('oro_web_catalog.web_catalog', false, false, $scopeIdentifier)) {
                return self::FEATURE_DISABLED;
            }

            return self::FEATURE_ENABLED;
        }

        return self::FEATURE_ABSTAIN;
    }
}
