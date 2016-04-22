<?php

namespace OroB2B\Bundle\ShippingBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\OroB2BShippingExtension;
use OroB2B\Bundle\ShippingBundle\Factory\ShippingOriginModelFactory;
use OroB2B\Bundle\ShippingBundle\Model\ShippingOrigin;

class ShippingOriginEventListener
{
    const KEY = 'shipping_origin';

    /** @var ShippingOriginModelFactory */
    protected $shippingOriginModelFactory;

    /**
     * @param ShippingOriginModelFactory $shippingOriginModelFactory
     */
    public function __construct(ShippingOriginModelFactory $shippingOriginModelFactory)
    {
        $this->shippingOriginModelFactory = $shippingOriginModelFactory;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $key = OroB2BShippingExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        if (!array_key_exists($key, $settings)) {
            return;
        }
        $settings[$key]['value'] = $this->shippingOriginModelFactory->create($settings[$key]['value']);
        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();
        $key = OroB2BShippingExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . self::KEY;

        if (empty($settings[$key]['value'])) {
            return;
        }
        $shippingOrigin = $settings[$key]['value'];
        if (!$shippingOrigin instanceof ShippingOrigin) {
            return;
        }
        $settings[$key]['value'] = [
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
