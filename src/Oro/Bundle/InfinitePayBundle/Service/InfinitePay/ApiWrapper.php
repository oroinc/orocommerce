<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\Logger\InfinitePayAPILoggerInterface;

class ApiWrapper
{
    /** @var InfinitePayAPI */
    protected $apiClient;

    /** @var InfinitePayAPILoggerInterface */
    protected $logger;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $wsdl;

    /**
     * @var InfinitePayConfigInterface
     */
    protected $config;

    public function __construct(
        InfinitePayConfigInterface $config,
        InfinitePayAPILoggerInterface $logger,
        array $options = [],
        $wsdl = null
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->options = $options;
        $this->wsdl = $wsdl;
    }

    /**
     * @return InfinitePayAPI
     */
    public function getClient()
    {
        if ($this->apiClient === null) {
            $this->apiClient = new InfinitePayAPI($this->config, $this->logger, $this->options, $this->wsdl);
        }

        return $this->apiClient;
    }
}
