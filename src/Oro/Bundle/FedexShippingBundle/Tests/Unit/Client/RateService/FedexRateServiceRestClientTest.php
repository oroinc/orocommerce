<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\AccessTokenProvider;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceRestClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\Factory\FedexRateServiceResponseFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FedexRateServiceRestClientTest extends TestCase
{
    /** @var RestClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $restClient;

    /** @var AccessTokenProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $accessTokenProvider;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var FedexRateServiceRestClient */
    private $client;

    #[\Override]
    protected function setUp(): void
    {
        $responseFactory = new FedexRateServiceResponseFactory();

        $this->accessTokenProvider = $this->createMock(AccessTokenProvider::class);
        $this->restClient = $this->createMock(RestClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $restClientFactory = $this->createMock(RestClientFactoryInterface::class);
        $restClientFactory->expects(self::any())
            ->method('createRestClient')
            ->with('https://apis.fedex.com', [])
            ->willReturn($this->restClient);

        $this->client = new FedexRateServiceRestClient(
            $this->accessTokenProvider,
            $restClientFactory,
            $responseFactory,
            $this->logger
        );
    }

    public function testSend(): void
    {
        $token = 'test_token';
        $request = new FedexRequest('test/uri', ['test' => 'data']);
        $settings = new FedexIntegrationSettings();

        $response = $this->createMock(RestResponseInterface::class);
        $response->expects(self::once())
            ->method('json')
            ->willReturn([
                'transactionId' => 'bc95c0e4-b33e-42a2-80d2-334282b5d37a',
                'output' => [
                    'rateReplyDetails' => [
                        [
                            'serviceType' => 'FEDEX_GROUND',
                            'ratedShipmentDetails' => [
                                [
                                    'totalNetCharge' => 10,
                                    'shipmentRateDetail' => ['currency' => 'USD']
                                ]
                            ]
                        ]

                    ]
                ]
            ]);

        $this->accessTokenProvider->expects(self::once())
            ->method('getAccessToken')
            ->with($settings, 'https://apis.fedex.com', false)
            ->willReturn($token);
        $this->restClient->expects(self::once())
            ->method('post')
            ->with(
                'test/uri',
                ['test' => 'data'],
                [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ]
            )
            ->willReturn($response);

        $this->logger->expects(self::never())
            ->method('warning');

        $result = $this->client->send($request, $settings);

        self::assertEquals(200, $result->getResponseStatusCode());
        self::assertEmpty($result->getErrors());
        self::assertEquals(['FEDEX_GROUND' => Price::create(10, 'USD')], $result->getPrices());
    }

    public function testSendWithExceptionDuringGettingAnAccessToken(): void
    {
        $errors = [[
            'code' => 'NOT.FOUND.ERROR',
            'message' => 'We are unable to process this request. Please try again later.'
        ]];
        $request = new FedexRequest('test/uri', ['test' => 'data']);
        $settings = new FedexIntegrationSettings();

        $this->accessTokenProvider->expects(self::once())
            ->method('getAccessToken')
            ->with($settings, 'https://apis.fedex.com', false)
            ->willThrowException(RestException::createFromResponse(new FakeRestResponse(
                401,
                [],
                \json_encode(['errors' => $errors])
            )));

        $this->restClient->expects(self::never())
            ->method('post');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Fedex rate REST request was failed.',
                [
                    'code' => 401,
                    'errors' => $errors
                ]
            );

        $result = $this->client->send($request, $settings);

        self::assertEquals(401, $result->getResponseStatusCode());
        self::assertEquals($errors, $result->getErrors());
        self::assertEmpty($result->getPrices());
    }

    public function testSendWithExceptionDuringGettingTheData(): void
    {
        $errors = [[
            'code' => 'INTERNAL.SERVER.ERROR',
            'message' => 'We encountered an unexpected error and are working to resolve the issue.'
        ]];
        $token = 'test_token';
        $request = new FedexRequest('test/uri', ['test' => 'data']);
        $settings = new FedexIntegrationSettings();

        $this->accessTokenProvider->expects(self::once())
            ->method('getAccessToken')
            ->with($settings, 'https://apis.fedex.com', false)
            ->willReturn($token);

        $this->restClient->expects(self::once())
            ->method('post')
            ->with(
                'test/uri',
                ['test' => 'data'],
                [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer ' . $token
                ]
            )
            ->willThrowException(RestException::createFromResponse(new FakeRestResponse(
                500,
                [],
                \json_encode(['errors' => $errors])
            )));

        $this->logger->expects(self::once())
            ->method('warning')
            ->with(
                'Fedex rate REST request was failed.',
                [
                    'code' => 500,
                    'errors' => $errors
                ]
            );

        $result = $this->client->send($request, $settings);

        self::assertEquals(500, $result->getResponseStatusCode());
        self::assertEquals($errors, $result->getErrors());
        self::assertEmpty($result->getPrices());
    }
}
