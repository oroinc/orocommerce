<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Triggers re-indexation of products when a default visibility was changed for categories or products.
 */
class DefaultVisibilityChangedListener
{
    private const OPTION_CATEGORY_VISIBILITY = 'oro_visibility.category_visibility';
    private const OPTION_PRODUCT_VISIBILITY = 'oro_visibility.product_visibility';

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onConfigUpdate(ConfigUpdateEvent $event): void
    {
        if ($this->isDefaultVisibilityChanged($event)) {
            $this->eventDispatcher->dispatch(
                new ReindexationRequestEvent([Product::class]),
                ReindexationRequestEvent::EVENT_NAME
            );
        }
    }

    private function isDefaultVisibilityChanged(ConfigUpdateEvent $event): bool
    {
        return
            $event->isChanged(self::OPTION_CATEGORY_VISIBILITY)
            || $event->isChanged(self::OPTION_PRODUCT_VISIBILITY);
    }
}
