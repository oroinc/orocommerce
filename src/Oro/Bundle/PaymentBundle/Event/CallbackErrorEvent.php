<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackErrorEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.error';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
