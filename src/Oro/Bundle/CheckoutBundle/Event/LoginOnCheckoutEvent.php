<?php

namespace Oro\Bundle\CheckoutBundle\Event;

/**
 * LoginOnCheckoutEvent represents logic which was performed by guest on checkout
 */
class LoginOnCheckoutEvent extends CheckoutEntityEvent
{
    public const string NAME = 'oro_checkout.login_on_guest_checkout';
}
