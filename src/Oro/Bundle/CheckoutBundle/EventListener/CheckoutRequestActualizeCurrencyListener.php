<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCurrency;

/**
 * Actualize checkout currency on checkout page load.
 */
class CheckoutRequestActualizeCurrencyListener
{
    public function __construct(
        private ActualizeCurrency $actualizeCurrency
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $this->actualizeCurrency->execute($event->getCheckout());
    }
}
