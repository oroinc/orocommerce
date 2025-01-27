<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\AddressValidation\Client\Response\Factory;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle\GuzzleRestResponse;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\AddressValidation\Client\Response\Factory\UPSAddressValidationResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class UPSAddressValidationResponseFactoryTest extends TestCase
{
    public function testCreateNoJsonResponse(): void
    {
        $responseFactory = new UPSAddressValidationResponseFactory();

        $guzzleResponse = new GuzzleRestResponse(new GuzzleResponse(body: json_encode('test')));
        $expectedResponse = new AddressValidationResponse(Response::HTTP_BAD_REQUEST);

        $response = $responseFactory->create($guzzleResponse);

        self::assertEquals($expectedResponse, $response);
    }

    public function testCreateEmptyResponse(): void
    {
        $responseFactory = new UPSAddressValidationResponseFactory();

        $guzzleResponse = new GuzzleRestResponse(new GuzzleResponse(body: json_encode([])));
        $expectedResponse = new AddressValidationResponse(Response::HTTP_OK, [], []);

        $response = $responseFactory->create($guzzleResponse);

        self::assertEquals($expectedResponse, $response);
    }

    public function testCreate(): void
    {
        $responseFactory = new UPSAddressValidationResponseFactory();
        $body = ['XAVResponse' => ['Candidate' => ['addresses'], 'Response' => ['Alert' => ['alert']]]];

        $guzzleResponse = new GuzzleRestResponse(new GuzzleResponse(body: json_encode($body)));
        $expectedResponse = new AddressValidationResponse(Response::HTTP_OK, ['addresses']);

        $response = $responseFactory->create($guzzleResponse);

        self::assertEquals($expectedResponse, $response);
    }

    public function testCreateExceptionResultGeneralException(): void
    {
        $responseFactory = new UPSAddressValidationResponseFactory();
        $exception = new \Exception('Test exception message');
        $expectedResponse = new AddressValidationResponse(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [],
            ['Test exception message']
        );

        $response = $responseFactory->createExceptionResult($exception);

        self::assertEquals($expectedResponse, $response);
    }

    public function testCreateExceptionResultRestException(): void
    {
        $responseFactory = new UPSAddressValidationResponseFactory();
        $exception = new RestException('Test rest exception message', Response::HTTP_BAD_REQUEST);
        $expectedResponse = new AddressValidationResponse(Response::HTTP_BAD_REQUEST, [], []);

        $response = $responseFactory->createExceptionResult($exception);

        self::assertEquals($expectedResponse, $response);
    }

    public function testCreateExceptionResultRestExceptionWithJsonErrors(): void
    {
        $body = ['response' => ['errors' => ['Test rest exception message']]];
        $guzzleResponse = new GuzzleRestResponse(new GuzzleResponse(body: json_encode($body)));

        $responseFactory = new UPSAddressValidationResponseFactory();
        $exception = new RestException('', Response::HTTP_BAD_REQUEST);
        $exception->setResponse($guzzleResponse);

        $expectedResponse = new AddressValidationResponse(
            Response::HTTP_BAD_REQUEST,
            [],
            ['Test rest exception message']
        );

        $response = $responseFactory->createExceptionResult($exception);

        self::assertEquals($expectedResponse, $response);
    }
}
