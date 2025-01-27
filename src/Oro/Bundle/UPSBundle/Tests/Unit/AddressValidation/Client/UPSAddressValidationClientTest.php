<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\AddressValidation\Client;

use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Fixture\Entity\TestTransport;
use Oro\Bundle\UPSBundle\AddressValidation\Client\Response\Factory\UPSAddressValidationResponseFactory;
use Oro\Bundle\UPSBundle\AddressValidation\Client\UPSAddressValidationClient;
use Oro\Bundle\UPSBundle\Client\AccessTokenProvider;
use Oro\Bundle\UPSBundle\Client\Factory\Basic\BasicUpsClientFactory;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class UPSAddressValidationClientTest extends TestCase
{
    private AccessTokenProvider|MockObject $tokenProvider;
    private BasicUpsClientFactory|MockObject $clientFactory;
    private UPSAddressValidationResponseFactory|MockObject $responseFactory;
    private LoggerInterface|MockObject $logger;

    private UPSAddressValidationClient $client;

    protected function setUp(): void
    {
        $this->tokenProvider = $this->createMock(AccessTokenProvider::class);
        $this->clientFactory = $this->createMock(BasicUpsClientFactory::class);
        $this->responseFactory = $this->createMock(UPSAddressValidationResponseFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new UPSAddressValidationClient(
            $this->tokenProvider,
            $this->clientFactory,
            $this->responseFactory,
            $this->logger
        );
    }

    public function testSendNotUPSTransport(): void
    {
        $request = new AddressValidationRequest('test/uri');
        $transport = new TestTransport();
        $message = sprintf(
            '%s client does not support %s transport.',
            UPSAddressValidationClient::class,
            TestTransport::class
        );
        $exception = new \InvalidArgumentException($message);
        $exceptionResponse = new AddressValidationResponse(Response::HTTP_BAD_REQUEST, [], [$message]);

        $this->responseFactory->expects(self::once())
            ->method('createExceptionResult')
            ->with($exception)
            ->willReturn($exceptionResponse);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('UPS Address Validation REST request was failed.', [
                'code' => $exceptionResponse->getResponseStatusCode(),
                'errors' => $exceptionResponse->getErrors()
            ]);

        $response = $this->client->send($request, $transport);

        self::assertEquals($exceptionResponse, $response);
    }

    /**
     * @dataProvider sendDataProvider
     */
    public function testSend(bool $mode): void
    {
        $request = new AddressValidationRequest('test/uri');
        $transport = new UPSTransport();
        $transport->setUPSTestMode($mode)
            ->setUpsClientId('id')
            ->setUpsClientSecret('secret');

        $client = $this->createMock(RestClientInterface::class);

        $this->tokenProvider->expects(self::once())
            ->method('getAccessToken')
            ->with($transport, $client, $request->isCheckMode())
            ->willReturn('token');

        $this->clientFactory->expects(self::once())
            ->method('setIsOAuthConfigured')
            ->with(true);
        $this->clientFactory->expects(self::once())
            ->method('createUpsClient')
            ->with($mode)
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
            ],
            'prod mode' => [
                'mode' => false,
            ],
        ];
    }
}
