<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class SystemConfigListener
{
    const SETTING = 'web_catalog';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [OroWebCatalogExtension::ALIAS, self::SETTING]);
        $settings = $event->getSettings();
        if (is_array($settings) && isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass(WebCatalog::class)
                ->find(WebCatalog::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!array_key_exists('value', $settings)) {
            return;
        }

        if (!$settings['value'] instanceof WebCatalog) {
            return;
        }

        $webCatalog = $settings['value'];
        $settings['value'] = $webCatalog->getId();
        $event->setSettings($settings);
    }
}
