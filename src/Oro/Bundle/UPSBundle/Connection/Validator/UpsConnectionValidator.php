<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\UpsConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Psr\Log\LoggerInterface;

class UpsConnectionValidator implements UpsConnectionValidatorInterface
{
    /**
     * @var UpsConnectionValidatorRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var UpsClientFactoryInterface
     */
    private $clientFactory;

    /**
     * @var UpsConnectionValidatorResultFactoryInterface
     */
    private $resultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        UpsConnectionValidatorRequestFactoryInterface $requestFactory,
        UpsClientFactoryInterface $clientFactory,
        UpsConnectionValidatorResultFactoryInterface $resultFactory,
        LoggerInterface $logger
    ) {
        $this->requestFactory = $requestFactory;
        $this->clientFactory = $clientFactory;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function validateConnectionByUpsSettings(UPSTransport $transport)
    {
        $request = $this->requestFactory->createByTransport($transport);
        $client = $this->clientFactory->createUpsClient($transport->isUpsTestMode());

        try {
            $response = $client->post($request->getUrl(), $request->getRequestData());
        } catch (RestException $e) {
            $this->logger->error($e->getMessage());

            return $this->resultFactory->createExceptionResult($e);
        }

        return $this->resultFactory->createResultByUpsClientResponse($response);
    }
}
