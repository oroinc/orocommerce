<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;

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
