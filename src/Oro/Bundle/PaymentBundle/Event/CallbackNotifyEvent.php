<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackNotifyEvent extends AbstractCallbackEvent
{
    public const NAME = 'oro_payment.callback.notify';

    #[\Override]
    public function getEventName()
    {
        return self::NAME;
    }
}
