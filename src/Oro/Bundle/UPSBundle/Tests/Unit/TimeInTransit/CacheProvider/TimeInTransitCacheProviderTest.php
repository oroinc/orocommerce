<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

class TimeInTransitCacheProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const BEFORE_LIFETIME_PROVIDER_CACHE_KEY = 'US:12345:US:12345:2018010112';

    /**
     * @internal
     */
    const CACHE_KEY = 'US:12345:US:12345:2018010112_transport_id';

    /**
     * @internal
     */
    const PICKUP_DATE = '01.01.2018 12:00';

    /**
     * @internal
     */
    const LIFETIME = 100;

    /**
     * @var TimeInTransitCacheProvider
     */
    private $timeInTransitCacheProvider;

    /**
     * @var UPSSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $settings;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /**
     * @var LifetimeProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $lifetimeProvider;

    /**
     * @var \DateTime
     */
    private $pickupDate;

    /**
     * @var AddressStub
     */
    private $address;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->address = new AddressStub();
        $this->pickupDate = \DateTime::createFromFormat('d.m.Y H:i', self::PICKUP_DATE);
        $this->settings = $this->createMock(UPSSettings::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->lifetimeProvider = $this->createMock(LifetimeProviderInterface::class);

        $this->lifetimeProvider->method('generateLifetimeAwareKey')
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
        $this->cacheProvider
            ->expects(static::once())
            ->method('contains')
            ->with(self::CACHE_KEY);

        $this->timeInTransitCacheProvider->contains($this->address, $this->address, $this->pickupDate);
    }

    public function testDelete()
    {
        $this->cacheProvider
            ->expects(static::once())
            ->method('delete')
            ->with(self::CACHE_KEY);

        $this->timeInTransitCacheProvider->delete($this->address, $this->address, $this->pickupDate);
    }

    public function testDeleteAll()
    {
        $this->cacheProvider
            ->expects(static::once())
            ->method('deleteAll');

        $this->timeInTransitCacheProvider->deleteAll();
    }

    public function testFetch()
    {
        $this->cacheProvider
            ->expects(static::once())
            ->method('fetch')
            ->with(self::CACHE_KEY);

        $this->timeInTransitCacheProvider->fetch($this->address, $this->address, $this->pickupDate);
    }

    public function testSave()
    {
        $timeInTransitResult = $this->createTimeInTransitResultMock();

        $this->cacheProvider
            ->expects(static::once())
            ->method('save')
            ->with(self::CACHE_KEY, $timeInTransitResult, self::LIFETIME);

        $lifetime = 10;

        $this->lifetimeProvider->method('getLifetime')
            ->with($this->settings, $lifetime)
            ->willReturn(self::LIFETIME);

        $this->timeInTransitCacheProvider
            ->save($this->address, $this->address, $this->pickupDate, $timeInTransitResult, $lifetime);
    }

    /**
     * @return TimeInTransitResultInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createTimeInTransitResultMock()
    {
        return $this->createMock(TimeInTransitResultInterface::class);
    }
}
