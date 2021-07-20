<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Store price list as a scalar value and restore object on config load.
 */
class FlatPriceListSystemConfigListener
{
    private const SETTING = 'default_price_list';

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var PriceListRelationTriggerHandlerInterface
     */
    private $triggerHandler;

    public function __construct(
        ManagerRegistry $registry,
        PriceListRelationTriggerHandlerInterface $triggerHandler
    ) {
        $this->registry = $registry;
        $this->triggerHandler = $triggerHandler;
    }

    public function onFormPreSetData(ConfigSettingsUpdateEvent $event)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', self::SETTING]);
        $settings = $event->getSettings();
        if (!empty($settings[$settingsKey]['value'])) {
            $settings[$settingsKey]['value'] = $this->registry
                ->getManagerForClass(PriceList::class)
                ->find(PriceList::class, $settings[$settingsKey]['value']);
            $event->setSettings($settings);
        }
    }

    public function onSettingsSaveBefore(ConfigSettingsUpdateEvent $event)
    {
        $settings = $event->getSettings();

        if (!is_array($settings) || !array_key_exists('value', $settings)) {
            return;
        }

        if (!$settings['value'] instanceof PriceList) {
            return;
        }

        $priceList = $settings['value'];
        $settings['value'] = $priceList->getId();
        $event->setSettings($settings);
    }

    public function updateAfter(ConfigUpdateEvent $event)
    {
        if ($event->isChanged('oro_pricing.default_price_list')) {
            if ($event->getScope() === 'website' && $event->getScopeId()) {
                /** @var Website $website */
                $website = $this->registry
                    ->getManagerForClass(Website::class)
                    ->find(Website::class, $event->getScopeId());
                $this->triggerHandler->handleWebsiteChange($website);
            } else {
                $this->triggerHandler->handleConfigChange();
            }
        }
    }
}
