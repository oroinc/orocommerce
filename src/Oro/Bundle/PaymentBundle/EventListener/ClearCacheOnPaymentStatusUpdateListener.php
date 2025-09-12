<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Listens for payment status update events and clears the cache of available payment statuses.
 */
final class ClearCacheOnPaymentStatusUpdateListener
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function onPaymentStatusUpdated(PaymentStatusUpdatedEvent $paymentStatusUpdatedEvent): void
    {
        $this->cache->clear();
    }
}
