<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Connection\Validator;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\UpsConnectionValidatorRequestFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory\UpsConnectionValidatorResultFactoryInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;
use Oro\Bundle\UPSBundle\Connection\Validator\UpsConnectionValidator;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Psr\Log\LoggerInterface;

class UpsConnectionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpsConnectionValidatorRequestFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $requestFactory;

    /** @var UpsClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var UpsConnectionValidatorResultFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $resultFactory;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var UpsConnectionValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->requestFactory = $this->createMock(UpsConnectionValidatorRequestFactoryInterface::class);
        $this->clientFactory = $this->createMock(UpsClientFactoryInterface::class);
        $this->resultFactory = $this->createMock(UpsConnectionValidatorResultFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->validator = new UpsConnectionValidator(
            $this->requestFactory,
            $this->clientFactory,
            $this->resultFactory,
            $this->logger
        );
    }

    public function testValidateConnectionByUpsSettings()
    {
        $transport= new UPSTransport();

        $request = $this->createMock(UpsClientRequestInterface::class);
        $client = $this->createMock(RestClientInterface::class);
        $response = $this->createMock(RestResponseInterface::class);
        $result = $this->createMock(UpsConnectionValidatorResultInterface::class);

        $this->requestFactory->expects(self::once())
            ->method('createByTransport')
            ->with($transport)
            ->willReturn($request);

        $this->clientFactory->expects(self::once())
            ->method('createUpsClient')
            ->willReturn($client);

        $client->expects(self::once())
            ->method('post')
            ->willReturn($response);

        $this->resultFactory->expects(self::once())
            ->method('createResultByUpsClientResponse')
            ->willReturn($result);

        self::assertSame($result, $this->validator->validateConnectionByUpsSettings($transport));
    }

    public function testValidateConnectionByUpsSettingsServerError()
    {
        $exception = new RestException();

        $transport = new UPSTransport();

        $request = $this->createMock(UpsClientRequestInterface::class);
        $client = $this->createMock(RestClientInterface::class);
        $result = $this->createMock(UpsConnectionValidatorResultInterface::class);

        $this->requestFactory->expects(self::once())
            ->method('createByTransport')
            ->with($transport)
            ->willReturn($request);

        $this->clientFactory->expects(self::once())
            ->method('createUpsClient')
            ->willReturn($client);

        $client->expects(self::once())
            ->method('post')
            ->willThrowException($exception);

        $this->resultFactory->expects(self::once())
            ->method('createExceptionResult')
            ->willReturn($result);

        $this->logger->expects(self::once())
            ->method('error')
            ->with($exception->getMessage());

        self::assertSame($result, $this->validator->validateConnectionByUpsSettings($transport));
    }
}
