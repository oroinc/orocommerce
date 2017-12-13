<?php

namespace Oro\Bundle\CheckoutBundle\Event;

class LoginOnCheckoutEvent extends CheckoutEntityEvent
{
    const NAME = 'oro_checkout.login_on_guest_checkout';
}
