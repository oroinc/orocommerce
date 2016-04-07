<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Event;

use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class CallbackReturnEventTest extends AbstractCallbackEventTest
{
    /**
     * @return CallbackReturnEvent
     */
    protected function getEvent()
    {
        return new CallbackReturnEvent();
    }
}
