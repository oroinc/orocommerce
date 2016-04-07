<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsAwareInterface;

interface RequestInterface extends OptionsAwareInterface
{
    /**
     * @return string
     */
    public function getAction();
}
