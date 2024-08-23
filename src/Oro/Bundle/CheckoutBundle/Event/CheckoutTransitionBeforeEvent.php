<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\WorkflowBundle\Event\Transition\TransitionEvent;

/**
 * Event to be thrown before checkout transition is made.
 */
class CheckoutTransitionBeforeEvent extends TransitionEvent
{
}
