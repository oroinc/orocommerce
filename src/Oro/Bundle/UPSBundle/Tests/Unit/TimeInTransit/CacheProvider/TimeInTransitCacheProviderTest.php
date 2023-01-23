<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider;

use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class TimeInTransitCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    private const BEFORE_LIFETIME_PROVIDER_CACHE_KEY = 'US:12345:US:12345:2018010112';
    private const CACHE_KEY = 'US:12345:US:12345:2018010112_transport_id';
    private const PICKUP_DATE = '01.01.2018 12:00';
    private const LIFETIME = 100;

    /** @var UPSSettings|\PHPUnit\Framework\MockObject\MockObject */
    private $settings;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var LifetimeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lifetimeProvider;

    /** @var \DateTime */
    private $pickupDate;

    /** @var AddressStub */
    private $address;

    /** @var TimeInTransitCacheProvider */
    private $timeInTransitCacheProvider;

    protected function setUp(): void
    {
        $this->address = new AddressStub();
        $this->pickupDate = \DateTime::createFromFormat('d.m.Y H:i', self::PICKUP_DATE);
        $this->settings = $this->createMock(UPSSettings::class);
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->lifetimeProvider = $this->createMock(LifetimeProviderInterface::class);

        $this->lifetimeProvider->expects(self::any())
            ->method('generateLifetimeAwareKey')
            ->with($this->settings, self::BEFORE_LIFETIME_PROVIDER_CACHE_KEY)
            ->willReturn(self::CACHE_KEY);

        $this->timeInTransitCacheProvider = new TimeInTransitCacheProvider(
            $this->settings,
            $this->cacheProvider,
            $this->lifetimeProvider
        );
    }

    public function testContains()
    {
        $this->cacheProvider->expects(self::once())
            ->method('hasItem')
            ->with(self::CACHE_KEY)
            ->willReturn(false);

        self::assertFalse(
            $this->timeInTransitCacheProvider->contains($this->address, $this->address, $this->pickupDate)
        );
    }

    public function testDelete()
    {
        $this->cacheProvider->expects(self::once())
            ->method('deleteItem')
            ->with(self::CACHE_KEY)
            ->willReturn(true);

        self::assertTrue($this->timeInTransitCacheProvider->delete($this->address, $this->address, $this->pickupDate));
    }

    public function testDeleteAll()
    {
        $this->cacheProvider->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        $this->timeInTransitCacheProvider->deleteAll();
    }

    public function testFetch()
    {
        $this->cacheProvider->expects(self::once())
            ->method('getItem')
            ->with(self::CACHE_KEY)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull($this->timeInTransitCacheProvider->fetch($this->address, $this->address, $this->pickupDate));
    }

    public function testSave()
    {
        $timeInTransitResult = $this->createMock(TimeInTransitResultInterface::class);

        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with(self::CACHE_KEY)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($timeInTransitResult)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(self::LIFETIME)
            ->willReturn($this->cacheItem);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        $lifetime = 10;

        $this->lifetimeProvider->expects(self::any())
            ->method('getLifetime')
            ->with($this->settings, $lifetime)
            ->willReturn(self::LIFETIME);

        self::assertTrue(
            $this->timeInTransitCacheProvider
                ->save($this->address, $this->address, $this->pickupDate, $timeInTransitResult, $lifetime)
        );
    }
}
