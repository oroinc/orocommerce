<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;

class CallbackErrorEventTest extends AbstractCallbackEventTest
{
    /**
     * @return CallbackErrorEvent
     */
    protected function getEvent()
    {
        return new CallbackErrorEvent();
    }
}
