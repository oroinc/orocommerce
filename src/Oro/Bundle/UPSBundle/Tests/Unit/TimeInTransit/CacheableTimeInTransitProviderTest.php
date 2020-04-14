<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit;

use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheableTimeInTransitProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\TimeInTransitProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\TimeInTransitProviderInterface;

class CacheableTimeInTransitProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const PICKUP_DATE = '01.01.2018 12:00';

    /**
     * @internal
     */
    const TRANSPORT_ID = 1;

    /**
     * @var TimeInTransitResultInterface
     */
    private $timeInTransitResult;

    /**
     * @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private $upsTransport;

    /**
     * @var TimeInTransitCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $timeInTransitCacheProvider;

    /**
     * @var TimeInTransitCacheProviderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $timeInTransitCacheProviderFactory;

    /**
     * @var TimeInTransitProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $timeInTransit;

    /**
     * @var CacheableTimeInTransitProvider
     */
    private $cacheableTimeInTransit;

    /**
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * @var AddressStub
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
        $this->pickupDate = \DateTime::createFromFormat('d.m.Y H:i', self::PICKUP_DATE);
        $this->upsTransport = $this->createMock(UPSTransport::class);
        $this->timeInTransitCacheProviderFactory = $this->createMock(TimeInTransitCacheProviderFactoryInterface::class);
        $this->timeInTransitCacheProvider = $this->createMock(TimeInTransitCacheProviderInterface::class);
        $this->timeInTransit = $this->createMock(TimeInTransitProvider::class);
        $this->timeInTransitResult = $this->createMock(TimeInTransitResultInterface::class);
        $this->cacheableTimeInTransit =
            new CacheableTimeInTransitProvider($this->timeInTransit, $this->timeInTransitCacheProviderFactory);
    }

    /**
     * @dataProvider timeInTransitResultStatusDataProvider
     *
     * @param bool $status
     * @param int  $saveCache
     */
    public function testGetTimeInTransitResult($status, $saveCache)
    {
        $this->mockTimeInTransitCacheProviderFactory();

        $this->timeInTransitCacheProvider
            ->expects(static::once())
            ->method('contains')
            ->with($this->address, $this->address, $this->pickupDate)
            ->willReturn(false);

        $this->timeInTransit
            ->expects(static::once())
            ->method('getTimeInTransitResult')
            ->with($this->upsTransport, $this->address, $this->address, $this->pickupDate)
            ->willReturn($this->timeInTransitResult);

        $this->timeInTransitResult
            ->expects(static::once())
            ->method('getStatus')
            ->willReturn($status);

        $this->timeInTransitCacheProvider
            ->expects(static::exactly($saveCache))
            ->method('save')
            ->with($this->address, $this->address, $this->pickupDate, $this->timeInTransitResult);

        $result = $this
            ->cacheableTimeInTransit
            ->getTimeInTransitResult(
                $this->upsTransport,
                $this->address,
                $this->address,
                $this->pickupDate,
                $this->weight
            );

        static::assertEquals($this->timeInTransitResult, $result);
    }

    /**
     * @return array
     */
    public function timeInTransitResultStatusDataProvider()
    {
        return [
            'result should be cached' => [
                'status' => true,
                'saveCache' => 1,
            ],
            'result should not be cached' => [
                'status' => false,
                'saveCache' => 0,
            ],
        ];
    }

    public function testGetTimeInTransitResultWhenCacheExists()
    {
        $this->mockTimeInTransitCacheProviderFactory();

        $this->timeInTransitCacheProvider
            ->expects(static::once())
            ->method('contains')
            ->with($this->address, $this->address, $this->pickupDate)
            ->willReturn(true);

        $this->timeInTransit
            ->expects(static::never())
            ->method('getTimeInTransitResult');

        $this->timeInTransitCacheProvider
            ->expects(static::once())
            ->method('fetch')
            ->with($this->address, $this->address, $this->pickupDate)
            ->willReturn($this->timeInTransitResult);

        $result = $this
            ->cacheableTimeInTransit
            ->getTimeInTransitResult(
                $this->upsTransport,
                $this->address,
                $this->address,
                $this->pickupDate,
                $this->weight
            );

        static::assertEquals($this->timeInTransitResult, $result);
    }

    private function mockTimeInTransitCacheProviderFactory()
    {
        $this->timeInTransitCacheProviderFactory
            ->expects(static::once())
            ->method('createCacheProviderForTransport')
            ->with($this->upsTransport)
            ->willReturn($this->timeInTransitCacheProvider);
    }
}
