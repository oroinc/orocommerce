<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\WorkflowBundle\Event\Transition\GuardEvent;

/**
 * Event to be thrown after checkout transition is made.
 */
class CheckoutTransitionAfterEvent extends GuardEvent
{
}
