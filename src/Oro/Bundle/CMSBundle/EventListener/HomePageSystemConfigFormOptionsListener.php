<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;

/**
 * Manages show/hide of {@see Configuration::HOME_PAGE} select in system configuration.
 * Appears only if a web catalog is not selected in system config.
 *
 * Removes "Use Default" checkbox for {@see Configuration::HOME_PAGE} option on the application configuration level.
 */
class HomePageSystemConfigFormOptionsListener
{
    public function onFormOptions(ConfigSettingsFormOptionsEvent $event): void
    {
        $homePageKey = Configuration::getConfigKeyByName(Configuration::HOME_PAGE);
        if (!$event->hasFormOptions($homePageKey)) {
            return;
        }

        if ($this->isAvailableWebCatalog($event)) {
            $event->unsetFormOptions($homePageKey);
            return;
        }

        if ($this->isApplicationLevel($event)) {
            $formOptions = $event->getFormOptions($homePageKey);
            $formOptions['resettable'] = false;
            $event->setFormOptions($homePageKey, $formOptions);
        }
    }

    private function isAvailableWebCatalog(ConfigSettingsFormOptionsEvent $event): bool
    {
        return (bool)$event->getConfigManager()->get(WebCatalogUsageProvider::SETTINGS_KEY);
    }

    private function isApplicationLevel(ConfigSettingsFormOptionsEvent $event): bool
    {
        return $event->getConfigManager()->getScopeEntityName() === GlobalScopeManager::SCOPE_NAME;
    }
}
