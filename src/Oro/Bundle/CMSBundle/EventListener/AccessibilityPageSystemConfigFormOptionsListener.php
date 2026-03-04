<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsFormOptionsEvent;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;

/**
 * Manages show/hide of {@see Configuration::ACCESSIBILITY_PAGE} select in system configuration.
 * Appears only if a web catalog is not selected in system config.
 */
class AccessibilityPageSystemConfigFormOptionsListener
{
    public function onFormOptions(ConfigSettingsFormOptionsEvent $event): void
    {
        $accessibilityPageKey = Configuration::getConfigKeyByName(Configuration::ACCESSIBILITY_PAGE);
        if (!$event->hasFormOptions($accessibilityPageKey)) {
            return;
        }

        if ($this->isAvailableWebCatalog($event)) {
            $event->unsetFormOptions($accessibilityPageKey);
        }
    }

    private function isAvailableWebCatalog(ConfigSettingsFormOptionsEvent $event): bool
    {
        return (bool)$event->getConfigManager()->get(WebCatalogUsageProvider::SETTINGS_KEY);
    }
}
