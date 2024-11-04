<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackReturnEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.return';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
