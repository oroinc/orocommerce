<?php

namespace Oro\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Bundle\TaxBundle\Factory\AddressModelFactory;
use Oro\Bundle\TaxBundle\Model\Address;

class AddressEventListener
{
    const KEY = 'origin_address';

    /**
     * @param AddressModelFactory $addressModelFactory
     */
    public function __construct(AddressModelFactory $addressModelFactory)
    {
        $this->addressModelFactory = $addressModelFactory;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroTaxExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $settings[$key]['value'] = $this->addressModelFactory->create($settings[$key]['value']);
        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroTaxExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . self::KEY;
        if (empty($settings[$key]['value'])) {
            return;
        }

        $address = $settings[$key]['value'];

        if (!$address instanceof Address) {
            return;
        }

        $settings[$key]['value'] = [
            'country' => $address->getCountry() ? $address->getCountry()->getIso2Code() : null,
            'region' => $address->getRegion() ? $address->getRegion()->getCombinedCode() : null,
            'region_text' => $address->getRegionText(),
            'postal_code' => $address->getPostalCode(),
        ];

        $event->setSettings($settings);
    }
}
