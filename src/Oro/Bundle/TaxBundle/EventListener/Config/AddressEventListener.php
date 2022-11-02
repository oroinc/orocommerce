<?php

namespace Oro\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\TaxBundle\DependencyInjection\Configuration;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\Address;

/**
 * Transforms address ID to Address entity and vise versa for the "origin address" configuration option.
 */
class AddressEventListener
{
    private const KEY = 'origin_address';

    private AddressModelFactory $addressModelFactory;

    public function __construct(AddressModelFactory $addressModelFactory)
    {
        $this->addressModelFactory = $addressModelFactory;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event): void
    {
        $settingsKey = Configuration::ROOT_NODE . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        $settings = $event->getSettings();
        if (\array_key_exists($settingsKey, $settings)) {
            $settings[$settingsKey]['value'] = $this->addressModelFactory->create($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (!\array_key_exists('value', $settings)) {
            return;
        }

        $address = $settings['value'];
        if (!$address instanceof Address) {
            return;
        }

        $settings['value'] = [
            'country' => $address->getCountry() ? $address->getCountry()->getIso2Code() : null,
            'region' => $address->getRegion() ? $address->getRegion()->getCombinedCode() : null,
            'region_text' => $address->getRegionText(),
            'postal_code' => $address->getPostalCode(),
        ];
        $event->setSettings($settings);
    }
}
