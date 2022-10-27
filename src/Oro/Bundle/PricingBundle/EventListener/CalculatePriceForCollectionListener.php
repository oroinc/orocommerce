<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\QuickAddCollectionPriceProvider;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;

/**
 * Adds price info to price provider
 */
class CalculatePriceForCollectionListener
{
    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    private $scopeCriteriaRequestHandler;

    /**
     * @var QuickAddCollectionPriceProvider
     */
    private $quickAddCollectionPriceProvider;

    public function __construct(
        QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
    ) {
        $this->quickAddCollectionPriceProvider = $quickAddCollectionPriceProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
    }

    public function onQuickAddRowsCollectionReady(QuickAddRowsCollectionReadyEvent $quickAddRowsCollectionReadyEvent)
    {
        $quickAddRowsCollection = $quickAddRowsCollectionReadyEvent->getCollection();

        if (!$quickAddRowsCollection->isEmpty()) {
            $this->quickAddCollectionPriceProvider->addPrices(
                $quickAddRowsCollection,
                $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
            );
        }
    }
}
