<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Context;

use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Creates a context for payment status calculation.
 */
class PaymentStatusCalculationContextFactory
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function createPaymentStatusCalculationContext(object $entity): PaymentStatusCalculationContext
    {
        $event = new PaymentStatusCalculationContextCollectEvent($entity);
        $event = $this->eventDispatcher->dispatch($event);

        return new PaymentStatusCalculationContext($event->getContextData());
    }
}
