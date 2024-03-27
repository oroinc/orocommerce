<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ActualizeCurrencyInterface;

/**
 * Actualize checkout currency on checkout page load.
 */
class CheckoutRequestActualizeCurrencyListener
{
    public function __construct(
        private ActualizeCurrencyInterface $actualizeCurrency
    ) {
    }

    public function onCheckoutRequest(CheckoutRequestEvent $event): void
    {
        $this->actualizeCurrency->execute($event->getCheckout());
    }
}
