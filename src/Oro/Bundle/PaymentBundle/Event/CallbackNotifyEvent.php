<?php

namespace Oro\Bundle\PaymentBundle\Event;

/**
 * Dispatched when a payment gateway sends a notification callback.
 *
 * This event is triggered when a payment gateway notifies the system about a transaction
 * status change, allowing listeners to process and respond to payment notifications.
 */
class CallbackNotifyEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.notify';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
