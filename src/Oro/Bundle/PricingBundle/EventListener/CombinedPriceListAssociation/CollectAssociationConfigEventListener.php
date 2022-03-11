<?php

namespace Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;

/**
 * Add config CPL to an event.
 */
class CollectAssociationConfigEventListener
{
    private PriceListCollectionProvider $collectionProvider;
    private CombinedPriceListProvider $combinedPriceListProvider;

    public function __construct(
        PriceListCollectionProvider $collectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider
    ) {
        $this->collectionProvider = $collectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
    }

    public function onCollectAssociations(CollectByConfigEvent $event): void
    {
        if (!$event->isCollectOnCurrentLevel()) {
            return;
        }

        $collection = $this->collectionProvider->getPriceListsByConfig();
        $collectionInfo = $this->combinedPriceListProvider->getCollectionInformation($collection);
        $event->addConfigAssociation($collectionInfo);
    }
}
