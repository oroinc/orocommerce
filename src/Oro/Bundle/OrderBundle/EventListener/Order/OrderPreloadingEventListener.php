<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

/**
 * Preloads order relations to reduce database queries.
 */
final class OrderPreloadingEventListener
{
    private array $preloadingConfig = [
        'lineItems' => [
            'product' => [
                'kitItems' => [],
                'unitPrecisions' => [
                    'unit' => [],
                ],
            ],
        ],
    ];

    public function __construct(
        private readonly PreloadingManager $preloadingManager,
    ) {
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    public function onOrderEvent(OrderEvent $event): void
    {
        $this->preloadingManager->preloadInEntities([$event->getOrder()], $this->preloadingConfig);
    }
}
