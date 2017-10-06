<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfig;

class PreviouslyPurchasedFeatureToggleListener
{
    /** @var ProductReindexManager */
    protected $productReindexManager;

    /**
     * @param ProductReindexManager $productReindexManager
     */
    public function __construct(ProductReindexManager $productReindexManager)
    {
        $this->productReindexManager = $productReindexManager;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function reindexProducts(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(OrderConfig::getConfigKey(OrderConfig::CONFIG_KEY_ENABLE_PURCHASE_HISTORY))) {
            $scope = $event->getScope();
            $websiteId = $scope == 'website' ? $event->getScopeId() : null;
            $this->productReindexManager->reindexAllProducts($websiteId);
        }
    }
}
