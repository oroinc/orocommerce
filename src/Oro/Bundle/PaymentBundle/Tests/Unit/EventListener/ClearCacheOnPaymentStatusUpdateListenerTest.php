<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use Oro\Bundle\PaymentBundle\EventListener\ClearCacheOnPaymentStatusUpdateListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

final class ClearCacheOnPaymentStatusUpdateListenerTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $cache;
    private ClearCacheOnPaymentStatusUpdateListener $listener;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->listener = new ClearCacheOnPaymentStatusUpdateListener($this->cache);
    }

    public function testOnPaymentStatusUpdatedClearCache(): void
    {
        $paymentStatus = $this->createMock(PaymentStatus::class);
        $targetEntity = new \stdClass();
        $event = new PaymentStatusUpdatedEvent($paymentStatus, $targetEntity);

        $this->cache
            ->expects(self::once())
            ->method('clear');

        $this->listener->onPaymentStatusUpdated($event);
    }
}
