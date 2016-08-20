<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackErrorEvent extends AbstractCallbackEvent
{
    const NAME = 'orob2b_payment.callback.error';

    /** {@inheritdoc} */
    public function getEventName()
    {
        return self::NAME;
    }
}
