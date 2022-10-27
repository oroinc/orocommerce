<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ShippingBundle\DependencyInjection\Configuration;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

/**
 * Transforms shipping origin ID to ShippingOrigin entity and vise versa for the "shipping origin" configuration option.
 */
class ShippingOriginEventListener
{
    private const KEY = 'shipping_origin';

    private ShippingOriginModelFactory $shippingOriginFactory;

    public function __construct(ShippingOriginModelFactory $shippingOriginFactory)
    {
        $this->shippingOriginFactory = $shippingOriginFactory;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event): void
    {
        $settingsKey = Configuration::ROOT_NODE . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        $settings = $event->getSettings();
        if (\array_key_exists($settingsKey, $settings)) {
            $settings[$settingsKey]['value'] = $this->shippingOriginFactory->create($settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event): void
    {
        $settings = $event->getSettings();
        if (!\array_key_exists('value', $settings)) {
            return;
        }

        $shippingOrigin = $settings['value'];
        if (!$shippingOrigin instanceof ShippingOrigin) {
            return;
        }

        $settings['value'] = [
            'country' => $shippingOrigin->getCountry() ? $shippingOrigin->getCountry()->getIso2Code() : null,
            'region' => $shippingOrigin->getRegion() ? $shippingOrigin->getRegion()->getCombinedCode() : null,
            'region_text' => $shippingOrigin->getRegionText(),
            'postalCode' => $shippingOrigin->getPostalCode(),
            'city' => $shippingOrigin->getCity(),
            'street' => $shippingOrigin->getStreet(),
            'street2' => $shippingOrigin->getStreet2(),
        ];
        $event->setSettings($settings);
    }
}
