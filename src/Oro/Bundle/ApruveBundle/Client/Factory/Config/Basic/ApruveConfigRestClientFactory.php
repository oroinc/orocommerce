<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory\Config\Basic;

use Oro\Bundle\ApruveBundle\Client\Factory\ApruveRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Factory\Config\ApruveConfigRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;

class ApruveConfigRestClientFactory implements ApruveConfigRestClientFactoryInterface
{
    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @param ApruveRestClientFactoryInterface $restClientFactory
     */
    public function __construct(ApruveRestClientFactoryInterface $restClientFactory)
    {
        $this->restClientFactory = $restClientFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ApruveConfigInterface $apruveConfig)
    {
        return $this->restClientFactory->create($apruveConfig->getApiKey(), $apruveConfig->isTestMode());
    }
}
