<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait ConsentFeatureTrait
{
    use ConfigManagerAwareTestTrait;

    public function enableConsentFeature(): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), true);
        $configManager->flush();

        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function disableConsentFeature(): void
    {
        $configManager = self::getConfigManager('global');
        $configManager->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), false);
        $configManager->flush();

        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function unsetDefaultWebCatalog(): void
    {
        $configManager = self::getConfigManager('global');

        $configManager->set(WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME, null);
        $configManager->flush();
    }
}
