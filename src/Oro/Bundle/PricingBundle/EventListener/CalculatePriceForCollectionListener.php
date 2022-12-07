<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\QuickAddCollectionPriceProvider;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\QuickAddRowsCollectionReadyEvent;

/**
 * Adds price info to price provider
 */
class CalculatePriceForCollectionListener
{
    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;

    private QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider;

    private ?ConfigManager $configManager = null;

    public function __construct(
        QuickAddCollectionPriceProvider $quickAddCollectionPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler
    ) {
        $this->quickAddCollectionPriceProvider = $quickAddCollectionPriceProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
    }

    public function setConfigManager(?ConfigManager $configManager): void
    {
        $this->configManager = $configManager;
    }

    public function onQuickAddRowsCollectionReady(QuickAddRowsCollectionReadyEvent $quickAddRowsCollectionReadyEvent)
    {
        $quickAddRowsCollection = $quickAddRowsCollectionReadyEvent->getCollection();

        if (!$quickAddRowsCollection->isEmpty()) {
            if ($this->isOptimizedFormEnabled()) {
                $this->quickAddCollectionPriceProvider->addAllPrices(
                    $quickAddRowsCollection,
                    $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
                );
            } else {
                $this->quickAddCollectionPriceProvider->addPrices(
                    $quickAddRowsCollection,
                    $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
                );
            }
        }
    }

    private function isOptimizedFormEnabled(): bool
    {
        if ($this->configManager) {
            return (bool)($this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::ENABLE_QUICK_ORDER_FORM_OPTIMIZED)
            ) ?? false);
        }

        return false;
    }
}
