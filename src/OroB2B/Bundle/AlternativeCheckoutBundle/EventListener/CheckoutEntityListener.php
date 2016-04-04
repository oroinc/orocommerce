<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\EventListener;

use OroB2B\Bundle\CheckoutBundle\Event\AbstractCheckoutEventListener;
use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;

class CheckoutEntityListener extends AbstractCheckoutEventListener
{
    /**
     * @return AlternativeCheckout
     */
    protected function createCheckoutEntity()
    {
        $checkout = new AlternativeCheckout();

        return $checkout;
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_alternative_checkout';
    }
}
