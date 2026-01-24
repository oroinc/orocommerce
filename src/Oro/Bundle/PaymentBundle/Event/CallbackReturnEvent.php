<?php

namespace Oro\Bundle\PaymentBundle\Event;

/**
 * Dispatched when a user returns from an external payment gateway.
 *
 * This event is triggered when a customer returns to the application after completing
 * or canceling payment at an external payment processor, allowing the system to process
 * the return and update transaction status accordingly.
 */
class CallbackReturnEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.return';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
