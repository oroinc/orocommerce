<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackReturnEvent extends AbstractCallbackEvent
{
    const NAME = 'orob2b_payment.callback.return';

    /** {@inheritdoc} */
    public function getEventName()
    {
        return self::NAME;
    }
}
