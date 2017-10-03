<?php

namespace Oro\Bundle\OrderBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\OrderBundle\DependencyInjection\Configuration as OrderConfig;

class PreviouslyPurchasedFeatureToggleListener
{
    /** @var ProductReindexManager */
    protected $reindexManager;

    /**
     * @param ProductReindexManager $reindexManager
     */
    public function __construct(ProductReindexManager $reindexManager)
    {
        $this->reindexManager = $reindexManager;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function reindexProducts(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(OrderConfig::getConfigKey(OrderConfig::CONFIG_KEY_ENABLE_PURCHASE_HISTORY))) {
            $scope = $event->getScope();
            if ($scope == 'website') {
                $this->reindexManager->triggerReindexationRequestEvent([], $event->getScopeId());
            } else {
                $this->reindexManager->triggerReindexationRequestEvent();
            }
        }
    }
}
