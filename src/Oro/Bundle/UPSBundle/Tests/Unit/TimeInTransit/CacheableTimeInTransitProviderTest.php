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
    private const PICKUP_DATE = '01.01.2018 12:00';

    /** @var TimeInTransitResultInterface */
    private $timeInTransitResult;

    /** @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $upsTransport;

    /** @var TimeInTransitCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $timeInTransitCacheProvider;

    /** @var TimeInTransitCacheProviderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $timeInTransitCacheProviderFactory;

    /** @var TimeInTransitProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $timeInTransit;

    /** @var CacheableTimeInTransitProvider */
    private $cacheableTimeInTransit;

    /** @var \DateTime */
    private $pickupDate;

    /** @var AddressStub */
    private $address;

    /** @var int */
    private $weight;

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
     */
    public function testGetTimeInTransitResult(bool $status, int $saveCache)
    {
        $this->mockTimeInTransitCacheProviderFactory();

        $this->timeInTransitCacheProvider->expects(self::once())
            ->method('contains')
            ->with($this->address, $this->address, $this->pickupDate)
            ->willReturn(false);

        $this->timeInTransit->expects(self::once())
            ->method('getTimeInTransitResult')
            ->with($this->upsTransport, $this->address, $this->address, $this->pickupDate)
            ->willReturn($this->timeInTransitResult);

        $this->timeInTransitResult->expects(self::once())
            ->method('getStatus')
            ->willReturn($status);

        $this->timeInTransitCacheProvider->expects(self::exactly($saveCache))
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

        self::assertEquals($this->timeInTransitResult, $result);
    }

    public function timeInTransitResultStatusDataProvider(): array
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

        $this->timeInTransitCacheProvider->expects(self::once())
            ->method('contains')
            ->with($this->address, $this->address, $this->pickupDate)
            ->willReturn(true);

        $this->timeInTransit->expects(self::never())
            ->method('getTimeInTransitResult');

        $this->timeInTransitCacheProvider->expects(self::once())
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

        self::assertEquals($this->timeInTransitResult, $result);
    }

    private function mockTimeInTransitCacheProviderFactory()
    {
        $this->timeInTransitCacheProviderFactory->expects(self::once())
            ->method('createCacheProviderForTransport')
            ->with($this->upsTransport)
            ->willReturn($this->timeInTransitCacheProvider);
    }
}
