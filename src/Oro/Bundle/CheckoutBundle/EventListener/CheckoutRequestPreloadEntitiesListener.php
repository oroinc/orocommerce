<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;

/**
 * Preload checkout entities on checkout page load.
 */
class CheckoutRequestPreloadEntitiesListener
{
    private array $preloadConfig = [];

    public function __construct(
        private PreloadingManager $preloadingManager
    ) {
    }

    public function setPreloadConfig(array $preloadConfig): void
    {
        $this->preloadConfig = $preloadConfig;
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $this->preloadingManager->preloadInEntities(
            $event->getCheckout()->getLineItems()?->toArray() ?? [],
            $this->preloadConfig
        );
    }
}
