<?php

namespace Oro\Bundle\CheckoutBundle\Exception;

use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;

/**
 * Thrown when a checkout cancellation transition is forbidden.
 */
class CancelCheckoutException extends ForbiddenTransitionException
{
}
