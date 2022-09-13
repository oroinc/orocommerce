<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * This class provides a clean interface for rebuilding combined price lists
 * and dispatches required events when CPLs are updated
 */
class CombinedPriceListsBuilderFacade
{
    private EventDispatcherInterface $dispatcher;
    private StrategyRegister $strategyRegister;
    private CombinedPriceListTriggerHandler $triggerHandler;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        StrategyRegister $strategyRegister,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->dispatcher = $dispatcher;
        $this->strategyRegister = $strategyRegister;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param iterable|CombinedPriceList[] $combinedPriceLists
     * @param array|Product[] $products
     */
    public function rebuild(iterable $combinedPriceLists, array $products = []): void
    {
        $strategy = $this->strategyRegister->getCurrentStrategy();
        foreach ($combinedPriceLists as $combinedPriceList) {
            $strategy->combinePrices($combinedPriceList, $products);
        }
    }

    /**
     * Process Combined Price Lists assignments information.
     * Triggers ProcessEvent, Listeners of this event will create relation between passed CPL and assignments.
     */
    public function processAssignments(
        CombinedPriceList $cpl,
        array $assignTo,
        ?int $version,
        bool $skipUpdateNotification = false,
    ): void {
        // Nothing to do if there are no assignments
        if (empty($assignTo)) {
            return;
        }

        $event = new ProcessEvent($cpl, $assignTo, $version, $skipUpdateNotification);
        $this->dispatcher->dispatch($event, $event::NAME);
    }

    /**
     * Trigger product indexation for a products.
     * Limited to websites that are associated with a given CPL.
     */
    public function triggerProductIndexation(
        CombinedPriceList $cpl,
        array $assignTo = [],
        array $productIds = []
    ): void {
        $event = new GetAssociatedWebsitesEvent($cpl, $assignTo);
        $this->dispatcher->dispatch($event, $event::NAME);
        foreach ($event->getWebsites() as $website) {
            $this->triggerHandler->processByProduct($cpl, $productIds, $website);
        }
    }
}
