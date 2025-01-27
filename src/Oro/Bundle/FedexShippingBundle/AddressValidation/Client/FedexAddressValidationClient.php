<?php

namespace Oro\Bundle\FedexShippingBundle\AddressValidation\Client;

use Oro\Bundle\AddressValidationBundle\Client\AddressValidationClientInterface;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequestInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponseInterface;
use Oro\Bundle\AddressValidationBundle\Client\Response\Factory\AddressValidationResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\AccessTokenProviderInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * FedEx Address Validation Rest API client.
 */
class FedexAddressValidationClient implements AddressValidationClientInterface
{
    public const string PRODUCTION_URL = 'https://apis.fedex.com';
    public const string TEST_URL = 'https://apis-sandbox.fedex.com';

    public const string ADDRESS_VALIDATION_URI = '/address/v1/addresses/resolve';

    public function __construct(
        private AccessTokenProviderInterface $accessTokenProvider,
        private RestClientFactoryInterface $restClientFactory,
        private AddressValidationResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger
    ) {
    }

    public function send(
        AddressValidationRequestInterface $request,
        Transport $transport
    ): AddressValidationResponseInterface {
        try {
            if (!$transport instanceof FedexIntegrationSettings) {
                throw new \InvalidArgumentException(
                    sprintf('%s client does not support %s transport.', \get_class($this), \get_class($transport)),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $baseUrl = $this->getUrl($transport->isFedexTestMode());

            $token = $this->accessTokenProvider->getAccessToken($transport, $baseUrl, $request->isCheckMode());
            $client = $this->restClientFactory->createRestClient($baseUrl, []);

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
                'Fedex Address Validation REST request was failed.',
                [
                    'code' => $responseResultObject->getResponseStatusCode(),
                    'errors' => $responseResultObject->getErrors()
                ]
            );
        }

        return $responseResultObject;
    }

    private function getUrl(bool $isTestMode): string
    {
        return $isTestMode ? static::TEST_URL : static::PRODUCTION_URL;
    }
}
