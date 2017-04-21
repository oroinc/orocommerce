<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;

class ApruveRestClientFactory implements ApruveRestClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @param RestClientFactoryInterface $restClientFactory
     */
    public function __construct(RestClientFactoryInterface $restClientFactory)
    {
        $this->restClientFactory = $restClientFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ApruveConfig $apruveConfig)
    {
        return new ApruveRestClient($apruveConfig, $this->restClientFactory);
    }
}
