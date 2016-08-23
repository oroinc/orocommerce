<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\CallbackNotifyEvent;

class CallbackNotifyEventTest extends AbstractCallbackEventTest
{
    /**
     * @return CallbackNotifyEvent
     */
    protected function getEvent()
    {
        return new CallbackNotifyEvent();
    }
}
