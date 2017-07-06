<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;

class SystemConfigListener
{
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
     * @param string          $ownerClass
     */
    public function __construct(ManagerRegistry $registry, $ownerClass)
    {
        $this->registry   = $registry;
        $this->ownerClass = $ownerClass;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER
        );
        $settings = $event->getSettings();
        if (isset($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass($this->ownerClass)
                ->find($this->ownerClass, $settings[$settingsKey]['value']);
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

        if (!is_a($settings['value'], $this->ownerClass)) {
            return;
        }

        /** @var object $owner */
        $owner = $settings['value'];
        $settings['value'] = $owner->getId();
        $event->setSettings($settings);
    }
}
