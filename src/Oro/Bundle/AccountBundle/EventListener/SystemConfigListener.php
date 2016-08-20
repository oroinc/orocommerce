<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\AccountBundle\DependencyInjection\OroAccountExtension;

class SystemConfigListener
{
    const SETTING = 'default_account_owner';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $ownerClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $userClass
     */
    public function __construct(ManagerRegistry $registry, $userClass)
    {
        $this->registry = $registry;
        $this->ownerClass = $userClass;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = $this->getSettingsKey();
        $settings = $event->getSettings();
        if (is_array($settings) && array_key_exists($settingsKey, $settings)) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass($this->ownerClass)
                ->find($this->ownerClass, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = $this->getSettingsKey();
        $settings = $event->getSettings();
        if (is_array($settings)
            && array_key_exists($settingsKey, $settings)
            && is_a($settings[$settingsKey]['value'], $this->ownerClass)
        ) {
            /** @var object $owner */
            $owner = $settings[$settingsKey]['value'];
            $settings[$settingsKey]['value'] = $owner->getId();
            $event->setSettings($settings);
        }
    }

    /**
     * @return string
     */
    protected function getSettingsKey()
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [OroAccountExtension::ALIAS, self::SETTING]);

        return $settingsKey;
    }
}
