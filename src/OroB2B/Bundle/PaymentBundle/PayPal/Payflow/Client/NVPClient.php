<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

use Guzzle\Http\ClientInterface as HTTPClient;

class NVPClient implements ClientInterface
{
    /** @var HTTPClient */
    protected $httpClient;

    /** @var EncoderInterface */
    protected $encoder;

    /**
     * @param HTTPClient $httpClient
     * @param EncoderInterface $encoder
     */
    public function __construct(HTTPClient $httpClient, EncoderInterface $encoder)
    {
        $this->httpClient = $httpClient;
        $this->encoder = $encoder;
    }

    /**
     * @param array $options
     * @return ResponseInterface
     */
    public function send(array $options = [])
    {
        $response = $this->httpClient
            ->post(NVPClient::PILOT_HOST_ADDRESS, [], $this->encoder->encode($options))
            ->send();

        return $this->encoder->decode($response->getBody(true));
    }
}
