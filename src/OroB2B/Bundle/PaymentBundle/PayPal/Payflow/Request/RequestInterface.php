<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getAction();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param array $options
     */
    public function setOptions(array $options = []);
}
