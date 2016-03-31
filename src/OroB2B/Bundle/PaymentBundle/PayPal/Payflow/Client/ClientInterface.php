<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

interface ClientInterface
{
    const PRODUCTION_HOST_ADDRESS = 'https://payflowpro.paypal.com';

    const PILOT_HOST_ADDRESS = 'https://pilot-payflowpro.paypal.com';

    /**
     * @param array $options
     * @return ResponseInterface
     */
    public function send(array $options = []);
}
