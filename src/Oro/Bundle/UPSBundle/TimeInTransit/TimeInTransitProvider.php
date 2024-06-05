<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\TimeInTransitResultFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides estimated arrival time
 */
class TimeInTransitProvider implements TimeInTransitProviderInterface
{
    public function __construct(
        private TimeInTransitRequestFactoryInterface $requestFactory,
        private UpsClientFactoryInterface $clientFactory,
        private TimeInTransitResultFactoryInterface $resultFactory,
        private AccessTokenProviderInterface $accessTokenProvider,
        private LoggerInterface $logger
    ) {
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

        $isOAuthConfigured = $this->isOAuthConfigured($transport);

        if ($this->clientFactory instanceof BasicUpsClientFactory) {
            $this->clientFactory->setIsOAuthConfigured($isOAuthConfigured);
        }
        $client = $this->clientFactory->createUpsClient($transport->isUpsTestMode());

        try {
            $headers = [];
            if ($isOAuthConfigured) {
                $token = $this->accessTokenProvider->getAccessToken($transport, $client);
                $headers = [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ];
            }

            $response = $client->post(
                $request->getUrl(),
                $request->getRequestData(),
                $headers
            );
        } catch (RestException $e) {
            $this->logger->error($e->getMessage());

            return $this->resultFactory->createExceptionResult($e);
        }

        return $this->resultFactory->createResultByUpsClientResponse($response);
    }

    private function isOAuthConfigured(UPSTransport $transport): bool
    {
        return
            !empty($transport->getUpsClientId())
            && !empty($transport->getUpsClientSecret());
    }
}
