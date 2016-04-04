<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

interface ClientInterface
{
    /**
     * @param string $hostAddress
     * @param array $options
     * @return ResponseInterface
     */
    public function send($hostAddress, array $options = []);
}
