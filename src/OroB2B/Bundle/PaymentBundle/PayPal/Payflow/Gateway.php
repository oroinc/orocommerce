<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\ClientInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsResolver;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Partner;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

class Gateway
{
    const PRODUCTION_HOST_ADDRESS = 'https://payflowpro.paypal.com';
    const PILOT_HOST_ADDRESS = 'https://pilot-payflowpro.paypal.com';

    /** @var ClientInterface */
    protected $client;

    /** @var RequestRegistry */
    protected $requestRegistry;

    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var bool */
    protected $testMode = false;

    /**
     * @param ClientInterface $client
     * @param RequestRegistry $requestRegistry
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(
        ClientInterface $client,
        RequestRegistry $requestRegistry,
        ProcessorRegistry $processorRegistry
    ) {
        $this->client = $client;
        $this->requestRegistry = $requestRegistry;
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @param string $action
     * @param array $options
     * @return ResponseInterface
     */
    public function request($action, array $options = [])
    {
        $resolver = new OptionsResolver();
        $request = $this->requestRegistry->getRequest($action);
        $request->configureOptions($resolver);

        $processor = $this->processorRegistry->getProcessor($options[Partner::PARTNER]);
        $processor->configureOptions($resolver);

        $responseData = $this->client->send($this->getHostName(), $resolver->resolve($options));

        return new Response($responseData);
    }

    /**
     * @return string
     */
    protected function getHostName()
    {
        return $this->testMode ? self::PILOT_HOST_ADDRESS : self::PRODUCTION_HOST_ADDRESS;
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;
    }

    /**
     * @return string
     */
    protected function getHostName()
    {
        return $this->testMode ? self::PILOT_HOST_ADDRESS : self::PRODUCTION_HOST_ADDRESS;
    }

    /**
     * @param bool $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = (bool)$testMode;
    }
}
