<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilderInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\BasicTimeInTransitRequestFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestBuilderFactoryInterface;

class BasicTimeInTransitRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicTimeInTransitRequestFactory
     */
    private $requestFactory;

    /**
     * @var TimeInTransitRequestBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $timeInTransitRequestBuilder;

    /**
     * @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private $upsTransport;

    /**
     * @var TimeInTransitRequestBuilderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $timeInTransitRequestBuilderFactory;

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
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->address = new AddressStub();
        $this->weight = 1;
        $this->pickupDate = new \DateTime();
        $this->upsTransport = $this->createMock(UPSTransport::class);
        $this->timeInTransitRequestBuilderFactory = $this
            ->createMock(TimeInTransitRequestBuilderFactoryInterface::class);
        $this->timeInTransitRequestBuilder = $this->createMock(TimeInTransitRequestBuilderInterface::class);

        $this->requestFactory = new BasicTimeInTransitRequestFactory($this->timeInTransitRequestBuilderFactory);
    }

    public function testCreateRequest()
    {
        $this->timeInTransitRequestBuilderFactory
            ->expects(static::once())
            ->method('createTimeInTransitRequestBuilder')
            ->with($this->upsTransport, $this->address, $this->address, $this->pickupDate)
            ->willReturn($this->timeInTransitRequestBuilder);

        $request = $this->createMock(UpsClientRequestInterface::class);
        $this->timeInTransitRequestBuilder
            ->expects(static::once())
            ->method('createRequest')
            ->willReturn($request);

        $this->upsTransport->method('getUpsUnitOfWeight')
            ->willReturn('unit');

        $expectedRequest = $this
            ->requestFactory
            ->createRequest($this->upsTransport, $this->address, $this->address, $this->pickupDate, $this->weight);

        static::assertSame($request, $expectedRequest);
    }
}
