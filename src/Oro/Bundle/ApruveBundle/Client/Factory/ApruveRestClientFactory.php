<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Psr\Log\LoggerInterface;

class ApruveRestClientFactory implements ApruveRestClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RestClientFactoryInterface $restClientFactory
     * @param LoggerInterface $logger
     */
    public function __construct(RestClientFactoryInterface $restClientFactory, LoggerInterface $logger)
    {
        $this->restClientFactory = $restClientFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ApruveConfigInterface $apruveConfig)
    {
        return new ApruveRestClient($apruveConfig, $this->restClientFactory, $this->logger);
    }
}
