<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\EventListener;

use OroB2B\Bundle\CheckoutBundle\EventListener\AbstractCheckoutEntityListener;
use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;

class CheckoutEntityListener extends AbstractCheckoutEntityListener
{
    /**
     * @return AlternativeCheckout
     */
    protected function createCheckoutEntity()
    {
        return new AlternativeCheckout();
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_alternative_checkout';
    }
}
