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

    /** @var bool */
    protected $testMode;

    /**
     * @param HTTPClient $httpClient
     * @param EncoderInterface $encoder
     * @param bool $testMode true - use pilot(test) host, otherwise use production
     */
    public function __construct(HTTPClient $httpClient, EncoderInterface $encoder, $testMode = true)
    {
        $this->httpClient = $httpClient;
        $this->encoder = $encoder;

        $this->setTestMode($testMode);
    }

    /**
     * @param bool $testMode true - use pilot(test) host, otherwise use production
     */
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;
    }

    /**
     * @param array $options
     * @return ResponseInterface
     */
    public function send(array $options = [])
    {
        $response = $this->httpClient
            ->post($this->getGatewayHost(), [], $this->encoder->encode($options))
            ->send();

        return $this->encoder->decode($response->getBody(true));
    }

    /**
     * @return string
     */
    protected function getGatewayHost()
    {
        return $this->testMode ? self::PILOT_HOST_ADDRESS : self::PRODUCTION_HOST_ADDRESS;
    }
}
