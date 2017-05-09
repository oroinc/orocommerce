<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\ClientInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\ResponseInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\RequestRegistry;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option\OptionsResolver;

class Gateway
{
    const ADDRESS_SANDBOX = \net\authorize\api\constants\ANetEnvironment::SANDBOX;
    const ADDRESS_PRODUCTION = \net\authorize\api\constants\ANetEnvironment::PRODUCTION;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var RequestRegistry
     */
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
        $this->client = $client;
        $this->requestRegistry = $requestRegistry;
    }

    /**
     * @param string $transactionType
     * @param array $options
     * @return ResponseInterface
     */
    public function request($transactionType, array $options = [])
    {
        $resolver = new OptionsResolver();
        $request = $this->requestRegistry->getRequest($transactionType);
        $request->configureOptions($resolver);

        return $this->client->send($this->getHostAddress(), $resolver->resolve($options));
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
    protected function getHostAddress()
    {
        return $this->testMode === true ?
            self::ADDRESS_SANDBOX :
            self::ADDRESS_PRODUCTION;
    }
}
