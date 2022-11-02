<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfig;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;

/**
 * Schedule products re-indexation on change of  the enable_purchase_history config option.
 */
class PreviouslyPurchasedFeatureToggleListener
{
    /** @var ProductReindexManager */
    protected $productReindexManager;

    public function __construct(ProductReindexManager $productReindexManager)
    {
        $this->productReindexManager = $productReindexManager;
    }

    public function reindexProducts(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(OrderConfig::getConfigKey(OrderConfig::CONFIG_KEY_ENABLE_PURCHASE_HISTORY))) {
            $this->productReindexManager->reindexAllProducts(null, true, ['order']);
        }
    }
}
