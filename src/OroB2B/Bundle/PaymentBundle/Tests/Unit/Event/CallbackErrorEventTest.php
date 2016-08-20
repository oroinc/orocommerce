<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;

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
