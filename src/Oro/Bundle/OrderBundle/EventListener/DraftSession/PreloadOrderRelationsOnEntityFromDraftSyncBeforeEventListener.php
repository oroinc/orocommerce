<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\EventListener\DraftSession;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Component\DraftSession\Event\EntityFromDraftSyncBeforeEvent;

/**
 * Preloads the order relations that are traversed by the order draft synchronizers
 * before the order is synchronized from its draft, to reduce the number of database queries.
 */
final class PreloadOrderRelationsOnEntityFromDraftSyncBeforeEventListener
{
    private array $preloadingConfig = [
        'lineItems' => [
            'product' => [],
            'parentProduct' => [],
            'productUnit' => [],
            'draftSource' => [
                'kitItemLineItems' => [],
            ],
            'kitItemLineItems' => [
                'kitItem' => [],
                'product' => [],
                'productUnit' => [],
            ],
        ],
        'discounts' => [],
        'billingAddress' => [
            'country' => [],
            'region' => [],
            'customerAddress' => [],
            'customerUserAddress' => [],
        ],
        'shippingAddress' => [
            'country' => [],
            'region' => [],
            'customerAddress' => [],
            'customerUserAddress' => [],
        ],
        'owner' => [],
        'organization' => [],
        'customer' => [],
        'customerUser' => [],
        'website' => [],
    ];

    public function __construct(
        private readonly PreloadingManager $preloadingManager,
    ) {
    }

    public function setPreloadingConfig(array $preloadingConfig): void
    {
        $this->preloadingConfig = $preloadingConfig;
    }

    public function onEntityFromDraftSyncBefore(EntityFromDraftSyncBeforeEvent $event): void
    {
        $source = $event->getSource();
        if (!$source instanceof Order) {
            // The event is also dispatched for nested order line items, but their relations
            // are preloaded together with the root order, so they are skipped here.
            return;
        }

        $target = $event->getTarget();

        $this->preloadingManager->preloadInEntities([$source, $target], $this->preloadingConfig);
    }
}
