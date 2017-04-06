<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\ClientInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\ResponseInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\RequestRegistry;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\OptionsResolver;

class Gateway
{
    /**
     * @var ClientInterface
     */
    protected $apiClient;

    /** @var RequestRegistry */
    protected $requestRegistry;

    /**
     * @var bool
     */
    protected $testMode = false;

    /**
     * @param ClientInterface $client
     * @param RequestRegistry $requestRegistry
     */
    public function __construct(ClientInterface $client, RequestRegistry $requestRegistry)
    {
        $this->apiClient = $client;
        $this->requestRegistry = $requestRegistry;
    }

    /**
     * @param string $transactionType
     * @param array $options
     * @return ResponseInterface
     */
    public function request($transactionType, array $options)
    {
        $options[Option\Environment::ENVIRONMENT] = $this->getEnvironment();

        $resolver = new OptionsResolver();
        $request = $this->requestRegistry->getRequest($transactionType);
        $request->configureOptions($resolver);

        return $this->apiClient->send($resolver->resolve($options));
    }

    /**
     * @param string $testMode
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * @return string
     */
    protected function getEnvironment()
    {
        return $this->testMode === true ?
            \net\authorize\api\constants\ANetEnvironment::SANDBOX :
            \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
    }
}
