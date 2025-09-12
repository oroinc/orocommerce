<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Event\PaymentStatusUpdatedEvent;
use PHPUnit\Framework\TestCase;

final class PaymentStatusUpdatedEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $paymentStatus = new PaymentStatus();
        $paymentStatus->setPaymentStatus('paid');
        $paymentStatus->setEntityClass('App\Entity\Order');
        $paymentStatus->setEntityIdentifier(123);

        $targetEntity = new \stdClass();

        $event = new PaymentStatusUpdatedEvent($paymentStatus, $targetEntity);

        self::assertSame($paymentStatus, $event->getPaymentStatus());
        self::assertSame($targetEntity, $event->getTargetEntity());
    }
}
