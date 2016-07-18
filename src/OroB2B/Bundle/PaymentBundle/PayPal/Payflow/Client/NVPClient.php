<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client;

use Guzzle\Http\ClientInterface as HTTPClientInterface;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;

class NVPClient implements ClientInterface
{
    /** @var HTTPClientInterface */
    protected $httpClient;

    /** @var EncoderInterface */
    protected $encoder;

    /**
     * @param HTTPClientInterface $httpClient
     * @param EncoderInterface $encoder
     */
    public function __construct(HTTPClientInterface $httpClient, EncoderInterface $encoder)
    {
        $this->httpClient = $httpClient;
        $this->encoder = $encoder;
    }

    /** {@inheritdoc} */
    public function send($hostAddress, array $options = [], array $connectionOptions = [])
    {
        $response = $this->httpClient
            ->post($hostAddress, [], $this->encoder->encode($options), $this->getRequestOptions($connectionOptions))
            ->send();

        return $this->encoder->decode($response->getBody(true));
    }

    /**
     * @param array $connectionOptions
     * @return array
     */
    protected function getRequestOptions(array $connectionOptions)
    {
        $requestOptions = [
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
