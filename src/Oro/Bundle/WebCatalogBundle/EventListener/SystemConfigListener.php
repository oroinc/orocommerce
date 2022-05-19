<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\Configuration;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

/**
 * Transforms web catalog ID to WebCatalog entity and vise versa for the "web catalog" configuration option.
 */
class SystemConfigListener
{
    private const KEY = 'web_catalog';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event): void
    {
        $settingsKey = Configuration::ROOT_NODE . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        $settings = $event->getSettings();
        if (\is_array($settings) && isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->doctrine
                ->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (!\array_key_exists('value', $settings)) {
            return;
        }
        if (!$settings['value'] instanceof WebCatalog) {
            return;
        }

        $settings['value'] = $settings['value']->getId();
        $event->setSettings($settings);
    }
}
