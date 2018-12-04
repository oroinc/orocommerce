<?php

namespace Oro\Bundle\ConsentBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConsentBundle\DependencyInjection\OroConsentExtension;
use Oro\Bundle\ContactUsBundle\Entity\ContactReason;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * This class prepare system setting value before save and reset config for selected consent contact reason
 * if it was removed.
 */
class SystemConfigListener
{
    const SETTING = 'consent_contact_reason';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, [OroConsentExtension::ALIAS, self::SETTING]);
        $settings = $event->getSettings();
        if (is_array($settings) && isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->doctrineHelper->getEntityManager(ContactReason::class)
                ->find(ContactReason::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!array_key_exists('value', $settings)) {
            return;
        }

        if (!$settings['value'] instanceof ContactReason) {
            return;
        }

        $contactReason = $settings['value'];
        $settings['value'] = $contactReason->getId();
        $event->setSettings($settings);
    }

    /**
     * @param ContactReason $contactReason
     */
    public function onPreRemove(ContactReason $contactReason)
    {
        $consentContactReasonId = (int) $this->configManager->get(
            sprintf('%s.%s', OroConsentExtension::ALIAS, self::SETTING)
        );

        if ($contactReason->getId() === $consentContactReasonId) {
            $this->configManager->reset(sprintf('%s.%s', OroConsentExtension::ALIAS, self::SETTING));
            $this->configManager->flush();
        }
    }
}
