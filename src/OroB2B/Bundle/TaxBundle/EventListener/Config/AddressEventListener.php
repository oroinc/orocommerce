<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;

use OroB2B\Bundle\TaxBundle\DependencyInjection\OroB2BTaxExtension;
use OroB2B\Bundle\TaxBundle\Model\Address;

class AddressEventListener
{
    const KEY = 'origin_address';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function formPreSet(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroB2BTaxExtension::ALIAS . ConfigManager::SECTION_VIEW_SEPARATOR . self::KEY;
        if (!array_key_exists($key, $settings)) {
            return;
        }

        $values = $settings[$key]['value'];
        $entity = new Address($values);

        if (!empty($values['country'])) {
            /** @var Country $country */
            $country = $this->doctrineHelper->getEntityReference('OroAddressBundle:Country', $values['country']);
            $entity->setCountry($country);
        }

        if (!empty($values['region'])) {
            /** @var Region $region */
            $region = $this->doctrineHelper->getEntityReference('OroAddressBundle:Region', $values['region']);
            $entity->setRegion($region);
        }

        $settings[$key]['value'] = $entity;

        $event->setSettings($settings);
    }

    /**
     * @param ConfigSettingsUpdateEvent $event
     */
    public function beforeSave(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        $key = OroB2BTaxExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . self::KEY;
        if (!array_key_exists($key, $settings)) {
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
