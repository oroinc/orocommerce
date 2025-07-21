<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Event;

/**
 * Generic interface for listeners to {@see AbstractCallbackEvent} event.
 * Can be used to make lazy services for final listener classes that cannot be proxied due to missing interface.
 */
interface PaymentCallbackListenerInterface
{
    public function onPaymentCallback(AbstractCallbackEvent $event): void;
}
