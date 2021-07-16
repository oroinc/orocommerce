<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Oro\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginEventListener
{
    const KEY = 'shipping_origin';

    /** @var ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    public function __construct(ShippingOriginModelFactory $shippingOriginModelFactory)
    {
        $this->shippingOriginModelFactory = $shippingOriginModelFactory;
    }

    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $key = OroShippingExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        if (!array_key_exists($key, $settings)) {
            return;
        }
        $settings[$key]['value'] = $this->shippingOriginModelFactory->create($settings[$key]['value']);
        $event->setSettings($settings);
    }

    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!array_key_exists('value', $settings)) {
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
