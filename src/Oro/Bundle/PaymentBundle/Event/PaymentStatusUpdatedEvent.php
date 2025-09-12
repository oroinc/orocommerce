<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is dispatched when the payment status is updated.
 */
class PaymentStatusUpdatedEvent extends Event
{
    public function __construct(private readonly PaymentStatus $paymentStatus, private readonly object $targetEntity)
    {
    }

    public function getPaymentStatus(): PaymentStatus
    {
        return $this->paymentStatus;
    }

    public function getTargetEntity(): object
    {
        return $this->targetEntity;
    }
}
