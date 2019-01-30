<?php

namespace Oro\Bundle\ConsentBundle\Tests\Functional\Entity;

use Oro\Bundle\ConsentBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogConfigChangeListener;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method ContainerInterface getContainer()
 */
trait ConsentFeatureTrait
{
    public function enableConsentFeature()
    {
        $this->getContainer()->get('oro_config.global')
            ->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), true);
        $this->getContainer()->get('oro_config.global')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function disableConsentFeature()
    {
        $this->getContainer()->get('oro_config.global')
            ->set(Configuration::getConfigKey(Configuration::CONSENT_FEATURE_ENABLED), false);
        $this->getContainer()->get('oro_config.global')->flush();
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    public function unsetDefaultWebCatalog()
    {
        $this->getContainer()->get('oro_config.global')->set(
            WebCatalogConfigChangeListener::WEB_CATALOG_CONFIGURATION_NAME,
            null
        );
        $this->getContainer()->get('oro_config.global')->flush();
    }
}
