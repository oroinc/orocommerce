<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\Request\Factory;

use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder\TimeInTransitRequestBuilderInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\BasicTimeInTransitRequestFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\Request\Factory\TimeInTransitRequestBuilderFactoryInterface;

class BasicTimeInTransitRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicTimeInTransitRequestFactory
     */
    private $requestFactory;

    /**
     * @var TimeInTransitRequestBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $timeInTransitRequestBuilder;

    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private $upsTransport;

    /**
     * @var TimeInTransitRequestBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->address = new AddressStub();
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

        $request = new \stdClass;
        $this->timeInTransitRequestBuilder
            ->expects(static::once())
            ->method('createRequest')
            ->willReturn($request);

        $expectedRequest = $this
            ->requestFactory
            ->createRequest($this->upsTransport, $this->address, $this->address, $this->pickupDate);

        static::assertSame($request, $expectedRequest);
    }
}
