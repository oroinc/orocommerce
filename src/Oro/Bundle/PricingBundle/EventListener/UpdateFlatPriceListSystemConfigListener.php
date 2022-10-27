<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates price list collections when a value of the "oro_pricing.default_price_list" configuration option is changed.
 */
class UpdateFlatPriceListSystemConfigListener
{
    private const CONFIG_KEY = 'oro_pricing.default_price_list';

    private ManagerRegistry $doctrine;
    private PriceListRelationTriggerHandlerInterface $triggerHandler;

    public function __construct(
        ManagerRegistry $doctrine,
        PriceListRelationTriggerHandlerInterface $triggerHandler
    ) {
        $this->doctrine = $doctrine;
        $this->triggerHandler = $triggerHandler;
    }

    public function onUpdateAfter(ConfigUpdateEvent $event): void
    {
        if ($event->isChanged(self::CONFIG_KEY)) {
            if ($event->getScope() === 'website' && $event->getScopeId()) {
                /** @var Website $website */
                $website = $this->doctrine->getManagerForClass(Website::class)
                    ->find(Website::class, $event->getScopeId());
                $this->triggerHandler->handleWebsiteChange($website);
            } else {
                $this->triggerHandler->handleConfigChange();
            }
        }
    }
}
