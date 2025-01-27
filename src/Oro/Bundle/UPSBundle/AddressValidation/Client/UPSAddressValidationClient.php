<?php

namespace Oro\Bundle\UPSBundle\AddressValidation\Client;

use Oro\Bundle\AddressValidationBundle\Client\AddressValidationClientInterface;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequestInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponseInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\Factory\AddressValidationResponseFactoryInterface;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Client\AccessTokenProviderInterface;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Psr\Log\LoggerInterface;

/**
 * UPS Address Validation Rest API client.
 */
class UPSAddressValidationClient implements AddressValidationClientInterface
{
    public const string ADDRESS_VALIDATION_URI = '/api/addressvalidation/v2/';

    public const int REQUEST_OPTION_ADDRESS_VALIDATION = 1;
    public const int REQUEST_OPTION_ADDRESS_CLASSIFICATION = 2;
    public const int REQUEST_OPTION_ADDRESS_VALIDATION_AND_CLASSIFICATION = 3;

    public function __construct(
        private AccessTokenProviderInterface $accessTokenProvider,
        private UpsClientFactoryInterface $clientFactory,
        private AddressValidationResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger
    ) {
    }

    public function send(
        AddressValidationRequestInterface $request,
        Transport $transport
    ): AddressValidationResponseInterface {
        try {
            if (!$transport instanceof UPSTransport) {
                throw new \InvalidArgumentException(
                    sprintf('%s client does not support %s transport.', \get_class($this), \get_class($transport))
                );
            }

            if ($this->clientFactory instanceof BasicUpsClientFactory) {
                $this->clientFactory->setIsOAuthConfigured($transport->isOAuthConfigured());
            }
            $client = $this->clientFactory->createUpsClient($transport->isUpsTestMode());

            $token = $this->accessTokenProvider->getAccessToken(
                $transport,
                $client,
                $request->isCheckMode()
            );

            $response = $client->post(
                $request->getUri(),
                $request->getRequestData(),
                [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ]
            );

            $responseResultObject = $this->responseFactory->create($response);
        } catch (\Exception $e) {
            $responseResultObject = $this->responseFactory->createExceptionResult($e);
        }

        if (!$responseResultObject->isSuccessful()) {
            $this->logger->error(
                'UPS Address Validation REST request was failed.',
                [
                    'code' => $responseResultObject->getResponseStatusCode(),
                    'errors' => $responseResultObject->getErrors(),
                ]
            );
        }

        return $responseResultObject;
    }
}
