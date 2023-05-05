<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\QuickAddCollectionPriceProvider;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;

/**
 * Adds prices to QuickAddRowCollection.
 */
class CalculatePriceForCollectionListener
{
    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;
    private QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider;

    public function __construct(
        QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
    ) {
        $this->quickAddCollectionPriceProvider = $quickAddCollectionPriceProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
    }

    public function onQuickAddRowsCollectionReady(
        QuickAddRowsCollectionReadyEvent $quickAddRowsCollectionReadyEvent
    ): void {
        $quickAddRowsCollection = $quickAddRowsCollectionReadyEvent->getCollection();
        if (!$quickAddRowsCollection->isEmpty()) {
            $this->quickAddCollectionPriceProvider->addAllPrices(
                $quickAddRowsCollection,
                $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
            );
        }
    }
}
