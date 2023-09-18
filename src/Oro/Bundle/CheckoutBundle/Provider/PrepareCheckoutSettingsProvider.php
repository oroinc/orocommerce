<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\PrepareCheckoutSettingsEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Action of service to trigger prepare checkout settings.
 */
class PrepareCheckoutSettingsProvider
{
    public function __construct(private EventDispatcher $dispatcher)
    {
    }

    public function prepareSettings(Checkout $checkout, mixed $settings): mixed
    {
        $event = new PrepareCheckoutSettingsEvent($checkout, $settings);
        $this->dispatcher->dispatch($event, PrepareCheckoutSettingsEvent::MODIFY);

        return $event->getSettings();
    }
}
