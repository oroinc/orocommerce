<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\AddressValidation\Client;

use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\FedexAddressValidationClient;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Response\Factory\FedexAddressValidationResponseFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\AccessTokenProvider;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity\TestTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class FedexAddressValidationClientTest extends TestCase
{
    private AccessTokenProvider|MockObject $tokenProvider;
    private RestClientFactoryInterface|MockObject $restClientFactory;
    private FedexAddressValidationResponseFactory|MockObject $responseFactory;
    private LoggerInterface|MockObject $logger;

    private FedexAddressValidationClient $client;

    protected function setUp(): void
    {
        $this->tokenProvider = $this->createMock(AccessTokenProvider::class);
        $this->restClientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->responseFactory = $this->createMock(FedexAddressValidationResponseFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new FedexAddressValidationClient(
            $this->tokenProvider,
            $this->restClientFactory,
            $this->responseFactory,
            $this->logger
        );
    }

    public function testSendNoFedexIntegrationSettings(): void
    {
        $request = new AddressValidationRequest('test/uri');
        $transport = new TestTransport();
        $message = sprintf(
            '%s client does not support %s transport.',
            FedexAddressValidationClient::class,
            TestTransport::class
        );
        $exception = new \InvalidArgumentException($message, Response::HTTP_BAD_REQUEST);
        $exceptionResponse = new AddressValidationResponse(Response::HTTP_BAD_REQUEST, [], [$message]);

        $this->responseFactory->expects(self::once())
            ->method('createExceptionResult')
            ->with($exception)
            ->willReturn($exceptionResponse);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Fedex Address Validation REST request was failed.', [
                'code' => $exceptionResponse->getResponseStatusCode(),
                'errors' => $exceptionResponse->getErrors()
            ]);

        $response = $this->client->send($request, $transport);

        self::assertEquals($exceptionResponse, $response);
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(bool $mode, string $url): void
    {
        $request = new AddressValidationRequest('test/uri');
        $transport = new FedexIntegrationSettings();
        $transport->setFedexTestMode($mode);

        $this->tokenProvider->expects(self::once())
            ->method('getAccessToken')
            ->with($transport, $url, $request->isCheckMode())
            ->willReturn('token');

        $client = $this->createMock(RestClientInterface::class);

        $this->restClientFactory->expects(self::once())
            ->method('createRestClient')
            ->with($url, [])
            ->willReturn($client);

        $restResponse = $this->createMock(RestResponseInterface::class);

        $client->expects(self::once())
            ->method('post')
            ->with(
                $request->getUri(),
                $request->getRequestData(),
                [
                    'content-type' => 'application/json',
                    'authorization' => 'Bearer token'
                ]
            )
            ->willReturn($restResponse);

        $this->responseFactory->expects(self::once())
            ->method('create')
            ->with($restResponse)
            ->willReturn(new AddressValidationResponse());

        $response = $this->client->send($request, $transport);

        self::assertEquals(new AddressValidationResponse(), $response);
    }

    public function sendDataProvider(): array
    {
        return [
            'test mode' => [
                'mode' => true,
                'url' => FedexAddressValidationClient::TEST_URL
            ],
            'prod mode' => [
                'mode' => false,
                'url' => FedexAddressValidationClient::PRODUCTION_URL
            ]
        ];
    }
}
