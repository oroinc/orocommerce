<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Event;

use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;

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
