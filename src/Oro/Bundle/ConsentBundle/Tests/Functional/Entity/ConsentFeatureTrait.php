<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;

trait ConsentFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    public function enableConsentFeature(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), true);
        $configManager->flush();

        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function disableConsentFeature(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), false);
        $configManager->flush();

        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function unsetDefaultWebCatalog(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_web_catalog.web_catalog', null);
        $configManager->flush();
    }
}
