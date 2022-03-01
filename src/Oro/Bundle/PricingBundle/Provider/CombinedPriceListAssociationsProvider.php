<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectEventFactoryInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Get CPL associations for a given website and target entity if any, otherwise for config
 */
class CombinedPriceListAssociationsProvider
{
    private CollectEventFactoryInterface $eventFactory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        CollectEventFactoryInterface $eventFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->eventFactory = $eventFactory;
    }

    public function getCombinedPriceListsWithAssociations(
        bool $force = false,
        Website $website = null,
        object $targetEntity = null
    ): array {
        $event = $this->eventFactory->createEvent($force, $website, $targetEntity);
        $this->eventDispatcher->dispatch($event, $event::NAME);

        return $event->getCombinedPriceListAssociations();
    }
}
