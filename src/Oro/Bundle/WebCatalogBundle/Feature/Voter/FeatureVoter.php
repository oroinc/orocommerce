<?php

namespace Oro\Bundle\WebCatalogBundle\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;

class FeatureVoter implements VoterInterface
{
    const FEATURE_NAME = 'frontend_master_catalog';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
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
