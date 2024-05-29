<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * FedEx Rate Rest API client.
 */
class FedexRateServiceRestClient implements FedexRateServiceBySettingsClientInterface
{
    public const PRODUCTION_URL = 'https://apis.fedex.com';
    public const TEST_URL = 'https://apis-sandbox.fedex.com';

    private AccessTokenProvider $accessTokenProvider;
    private RestClientFactoryInterface $restClientFactory;
    private FedexRateServiceResponseFactoryInterface $responseFactory;
    private LoggerInterface $logger;

    public function __construct(
        AccessTokenProvider $accessTokenProvider,
        RestClientFactoryInterface $restClientFactory,
        FedexRateServiceResponseFactoryInterface $responseFactory,
        LoggerInterface $logger
    ) {
        $this->accessTokenProvider = $accessTokenProvider;
        $this->restClientFactory = $restClientFactory;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    public function send(
        FedexRequestInterface $request,
        FedexIntegrationSettings $settings
    ): FedexRateServiceResponseInterface {
        $baseUrl = $this->getUrl($settings);

        try {
            $token = $this->accessTokenProvider->getAccessToken(
                $settings,
                $baseUrl,
                $request->isCheckMode()
            );
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
            $this->logger->warning(
                'Fedex rate REST request was failed.',
                [
                    'code' => $responseResultObject->getResponseStatusCode(),
                    'errors' => $responseResultObject->getErrors()
                ]
            );
        }

        return $responseResultObject;
    }

    private function getUrl(FedexIntegrationSettings $settings): string
    {
        if ($settings->isFedexTestMode()) {
            return self::TEST_URL;
        }

        return self::PRODUCTION_URL;
    }
}
