<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackReturnEvent extends AbstractCallbackEvent
{
    public const NAME = 'oro_payment.callback.return';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
