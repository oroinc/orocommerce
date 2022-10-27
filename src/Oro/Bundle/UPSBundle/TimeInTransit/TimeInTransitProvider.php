<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\TimeInTransitResultFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Psr\Log\LoggerInterface;

class TimeInTransitProvider implements TimeInTransitProviderInterface
{
    /**
     * @var TimeInTransitRequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var UpsClientFactoryInterface
     */
    private $clientFactory;

    /**
     * @var TimeInTransitResultFactoryInterface
     */
    private $resultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TimeInTransitRequestFactoryInterface $requestFactory,
        UpsClientFactoryInterface $clientFactory,
        TimeInTransitResultFactoryInterface $resultFactory,
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
    public function getTimeInTransitResult(
        UPSTransport $transport,
        AddressInterface $shipFromAddress,
        AddressInterface $shipToAddress,
        \DateTime $pickupDate,
        int $weight
    ): TimeInTransitResultInterface {
        $request = $this->requestFactory->createRequest(
            $transport,
            $shipFromAddress,
            $shipToAddress,
            $pickupDate,
            $weight
        );
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
