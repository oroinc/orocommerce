<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\UpsConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Psr\Log\LoggerInterface;

/**
 *  Basic implementation of UPS Connection Validator
 */
class UpsConnectionValidator implements UpsConnectionValidatorInterface
{
    public function __construct(
        private UpsConnectionValidatorRequestFactoryInterface $requestFactory,
        private UpsClientFactoryInterface $clientFactory,
        private UpsConnectionValidatorResultFactoryInterface $resultFactory,
        private AccessTokenProviderInterface $accessTokenProvider,
        private LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function validateConnectionByUpsSettings(UPSTransport $transport): UpsConnectionValidatorResultInterface
    {
        $request = $this->requestFactory->createByTransport($transport);

        $isOAuthConfigured = $this->isOAuthConfigured($transport);
        if ($this->clientFactory instanceof BasicUpsClientFactory) {
            $this->clientFactory->setIsOAuthConfigured($isOAuthConfigured);
        }

        $client = $this->clientFactory
            ->createUpsClient($transport->isUpsTestMode());

        try {
            $headers = [];
            if ($isOAuthConfigured) {
                $token = $this->accessTokenProvider->getAccessToken($transport, $client, true);
                $headers = [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ];
            }

            $response = $client->post($request->getUrl(), $request->getRequestData(), $headers);
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
