<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Client;

use GuzzleHttp\ClientInterface as HTTPClientInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\NVP\EncoderInterface;

/**
 * HTTP client for the PayPal NVP API
 */
class NVPClient implements ClientInterface
{
    /** @var HTTPClientInterface */
    protected $httpClient;

    /** @var EncoderInterface */
    protected $encoder;

    public function __construct(HTTPClientInterface $httpClient, EncoderInterface $encoder)
    {
        $this->httpClient = $httpClient;
        $this->encoder = $encoder;
    }

    /** {@inheritdoc} */
    public function send($hostAddress, array $options = [], array $connectionOptions = [])
    {
        $body = $this->encoder->encode($options);
        $response = $this->httpClient->request(
            'POST',
            $hostAddress,
            $this->getRequestOptions($body, $connectionOptions)
        );

        return $this->encoder->decode((string)$response->getBody());
    }

    protected function getRequestOptions(string $body, array $connectionOptions): array
    {
        $requestOptions = [
            'body' => $body,
            'verify' => isset($connectionOptions[self::SSL_VERIFY]) ? (bool)$connectionOptions[self::SSL_VERIFY] : true,
        ];

        if (isset($connectionOptions[self::PROXY_HOST], $connectionOptions[self::PROXY_PORT])) {
            $requestOptions['proxy'] = sprintf(
                '%s:%d',
                $connectionOptions[self::PROXY_HOST],
                $connectionOptions[self::PROXY_PORT]
            );
        }

        return $requestOptions;
    }
}
