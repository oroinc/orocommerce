<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Provider\QuickAddCollectionPriceProvider;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;

class CalculatePriceForCollectionListener
{
    /**
     * @var PriceListRequestHandler
     */
    private $priceListRequestHandler;

    /**
     * @var QuickAddCollectionPriceProvider
     */
    private $quickAddCollectionPriceProvider;

    /**
     * @param QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->quickAddCollectionPriceProvider = $quickAddCollectionPriceProvider;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param QuickAddRowsCollectionReadyEvent $quickAddRowsCollectionReadyEvent
     */
    public function onQuickAddRowsCollectionReady(QuickAddRowsCollectionReadyEvent $quickAddRowsCollectionReadyEvent)
    {
        // TODO: BB-14587
        /** @var CombinedPriceList $priceList */
        $priceList = $this->priceListRequestHandler->getPriceListByCustomer();
        if (!$priceList) {
            return;
        }

        $quickAddRowsCollection = $quickAddRowsCollectionReadyEvent->getCollection();

        if (!$quickAddRowsCollection->isEmpty()) {
            $this->quickAddCollectionPriceProvider->addPrices(
                $quickAddRowsCollection,
                $priceList
            );
        }
    }
}
