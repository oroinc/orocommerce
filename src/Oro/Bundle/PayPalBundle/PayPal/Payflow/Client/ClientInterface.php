<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Client;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;

interface ClientInterface
{
    const SSL_VERIFY = 'SSL_VERIFY';
    const PROXY_HOST = 'PROXY_HOST';
    const PROXY_PORT = 'PROXY_PORT';

    /**
     * @param string $hostAddress
     * @param array $options
     * @param array $connectionOptions
     * @return ResponseInterface
     */
    public function send($hostAddress, array $options = [], array $connectionOptions = []);
}
