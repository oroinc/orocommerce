<?php

namespace Oro\Bundle\ConsentBundle\Feature\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\FeatureToggleBundle\Checker\Voter\VoterInterface;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;

/**
 * Decides whatever consents feature enabled depending on configuration and request type
 */
class FeatureVoter implements VoterInterface
{
    const FEATURE_NAME = 'consents';

    /** @var ConfigManager */
    private $configManager;

    /** @var FrontendHelper */
    private $frontendHelper;

    /**
     * @param ConfigManager $configManager
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(ConfigManager $configManager, FrontendHelper $frontendHelper)
    {
        $this->configManager = $configManager;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        if ($feature === self::FEATURE_NAME && $this->frontendHelper->isFrontendRequest()) {
            $configKey = sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::ENABLED_CONSENTS);
            if (!$this->configManager->get($configKey, false, false, $scopeIdentifier)) {
                return self::FEATURE_DISABLED;
            }

            return self::FEATURE_ENABLED;
        }

        return self::FEATURE_ABSTAIN;
    }
}
