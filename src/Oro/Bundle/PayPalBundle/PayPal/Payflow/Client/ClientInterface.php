<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Client;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\ResponseInterface;

/**
 * Defines the contract for PayPal Payflow API client implementations.
 *
 * Handles communication with PayPal Payflow servers, sending transaction requests
 * and receiving responses with support for SSL verification and proxy settings.
 */
interface ClientInterface
{
    public const SSL_VERIFY = 'SSL_VERIFY';
    public const PROXY_HOST = 'PROXY_HOST';
    public const PROXY_PORT = 'PROXY_PORT';

    /**
     * @param string $hostAddress
     * @param array $options
     * @param array $connectionOptions
     * @return ResponseInterface
     */
    public function send($hostAddress, array $options = [], array $connectionOptions = []);
}
