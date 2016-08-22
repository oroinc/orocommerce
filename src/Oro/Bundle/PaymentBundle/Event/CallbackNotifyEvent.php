<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackNotifyEvent extends AbstractCallbackEvent
{
    const NAME = 'orob2b_payment.callback.notify';

    /** {@inheritdoc} */
    public function getEventName()
    {
        return self::NAME;
    }
}
