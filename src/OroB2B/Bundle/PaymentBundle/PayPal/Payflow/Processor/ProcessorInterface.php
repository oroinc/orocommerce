<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsAwareInterface;

interface ProcessorInterface extends OptionsAwareInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getCode();
}
