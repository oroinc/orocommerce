<?php

namespace Oro\Bundle\CheckoutBundle\Event;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to be fired on the beginning of CheckoutController::checkoutAction
 */
class CheckoutRequestEvent extends Event
{
    public function __construct(private Request $request, private Checkout $checkout)
    {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getCheckout(): Checkout
    {
        return $this->checkout;
    }
}
