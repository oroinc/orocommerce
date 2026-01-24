<?php

namespace Oro\Bundle\PaymentBundle\Event;

/**
 * Dispatched when a payment callback error occurs.
 *
 * This event is triggered when a payment gateway returns an error response during
 * callback processing, allowing listeners to handle payment errors appropriately.
 */
class CallbackErrorEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.error';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
