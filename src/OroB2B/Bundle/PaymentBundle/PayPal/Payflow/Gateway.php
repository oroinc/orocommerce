<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Client\ClientInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\OptionsResolver;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Processor\ProcessorRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseInterface;

class Gateway
{
    /** @var ClientInterface */
    protected $client;

    /** @var ProcessorRegistry */
    protected $processorRegistry;

    /** @var RequestRegistry */
    protected $requestRegistry;

    /**
     * @param ClientInterface $client
     * @param ProcessorRegistry $processorRegistry
     * @param RequestRegistry $requestRegistry
     */
    public function __construct(
        ClientInterface $client,
        ProcessorRegistry $processorRegistry,
        RequestRegistry $requestRegistry
    ) {
        $this->client = $client;
        $this->processorRegistry = $processorRegistry;
        $this->requestRegistry = $requestRegistry;
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

        $processor = $this->processorRegistry->getProcessor($options['PARTNER']);
        $processor->configureOptions($resolver);

        $response = $this->client->send($resolver->resolve($options));

        // @todo create response

        return $response;
    }
}
