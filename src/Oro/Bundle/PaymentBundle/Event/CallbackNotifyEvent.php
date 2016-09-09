<?php

namespace Oro\Bundle\PaymentBundle\Event;

class CallbackNotifyEvent extends AbstractCallbackEvent
{
    const NAME = 'oro_payment.callback.notify';

    /** {@inheritdoc} */
    public function getEventName()
    {
        return self::NAME;
    }
}
