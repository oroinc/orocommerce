<?php

namespace OroB2B\Bundle\CheckoutBundle\EventListener;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;

class CheckoutEntityListener extends AbstractCheckoutEntityListener
{
    /**
     * @var string
     */
    protected $checkoutClassName = 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout';

    /**
     * @param string $checkoutClassName
     */
    public function setCheckoutClassName($checkoutClassName)
    {
        $this->checkoutClassName = $checkoutClassName;
    }

    /**
     * @return string
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_checkout';
    }

    /**
     * @return CheckoutInterface
     */
    protected function createCheckoutEntity()
    {
        return new $this->checkoutClassName();
    }
}
