<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory\TimeInTransitResultFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\TimeInTransitProvider;
use Psr\Log\LoggerInterface;

class TimeInTransitProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TimeInTransitRequestFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestFactory;

    /**
     * @var UpsClientFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $clientFactory;

    /**
     * @var TimeInTransitResultFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resultFactory;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * @var AddressInterface
     */
    private $address;

    /**
     * @var int
     */
    private $weight;

    /**
     * @var TimeInTransitProvider
     */
    private $timeInTransit;

    protected function setUp(): void
    {
        $this->address = new AddressStub();
        $this->weight = 1;
        $this->pickupDate = new \DateTime();
        $this->requestFactory = $this->createMock(TimeInTransitRequestFactoryInterface::class);
        $this->clientFactory = $this->createMock(UpsClientFactoryInterface::class);
        $this->resultFactory = $this->createMock(TimeInTransitResultFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->timeInTransit = new TimeInTransitProvider(
            $this->requestFactory,
            $this->clientFactory,
            $this->resultFactory,
            $this->logger
        );
    }

    public function testValidateConnectionByUpsSettings()
    {
        $transport = new UPSTransport();

        $request = $this->createMock(UpsClientRequestInterface::class);
        $client = $this->createMock(RestClientInterface::class);
        $response = $this->createMock(RestResponseInterface::class);
        $result = $this->createMock(TimeInTransitResultInterface::class);

        $this->requestFactory
            ->expects(static::once())
            ->method('createRequest')
            ->with($transport)
            ->willReturn($request);

        $this->clientFactory
            ->expects(static::once())
            ->method('createUpsClient')
            ->willReturn($client);

        $client
            ->expects(static::once())
            ->method('post')
            ->willReturn($response);

        $this->resultFactory
            ->expects(static::once())
            ->method('createResultByUpsClientResponse')
            ->willReturn($result);

        $result = $this->timeInTransit
            ->getTimeInTransitResult($transport, $this->address, $this->address, $this->pickupDate, $this->weight);

        static::assertSame($result, $result);
    }

    public function testValidateConnectionByUpsSettingsServerError()
    {
        $transport = new UPSTransport();

        $request = $this->createMock(UpsClientRequestInterface::class);
        $client = $this->createMock(RestClientInterface::class);
        $result = $this->createMock(TimeInTransitResultInterface::class);

        $this->requestFactory
            ->expects(static::once())
            ->method('createRequest')
            ->with($transport)
            ->willReturn($request);

        $this->clientFactory
            ->expects(static::once())
            ->method('createUpsClient')
            ->willReturn($client);

        $client
            ->expects(static::once())
            ->method('post')
            ->willThrowException(new RestException);

        $this->resultFactory
            ->expects(static::once())
            ->method('createExceptionResult')
            ->willReturn($result);

        $result = $this->timeInTransit
            ->getTimeInTransitResult($transport, $this->address, $this->address, $this->pickupDate, $this->weight);

        static::assertSame($result, $result);
    }
}
