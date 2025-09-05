<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\EventListener\PaymentStatusCalculationContext;

use Oro\Bundle\PaymentBundle\Event\PaymentStatusCalculationContextCollectEvent;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Collects payment transactions for the payment status calculation context.
 * This listener is triggered at the beginning of the payment status calculation process.
 */
final class SetTotalForPaymentStatusCalculationContextListener
{
    public function __construct(private readonly TotalProcessorProvider $totalProcessorProvider)
    {
    }

    public function onPaymentStatusCalculationContextCollect(PaymentStatusCalculationContextCollectEvent $event): void
    {
        if ($event->getContextItem('total') !== null) {
            return;
        }

        $event->setContextItem('total', $this->totalProcessorProvider->getTotal($event->getEntity()));
    }
}
