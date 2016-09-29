<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay\Logger;

use Psr\Log\LoggerInterface;

class InfinitePayAPILogger implements InfinitePayAPILoggerInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param string $request
     * @param string $response
     */
    public function logApiError($request, $response)
    {
        $debugLog = [
            'request' => $request,
            'response' => $response,
        ];

        $this->logger->debug(json_encode($debugLog), ['service' => 'InfiniteAPILogger']);
    }
}
