<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider\Factory;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;

class TimeInTransitCacheProviderFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TimeInTransitCacheProviderFactory
     */
    private $timeInTransitCacheProviderFactory;

    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->timeInTransitCacheProviderFactory = new TimeInTransitCacheProviderFactory($this->cacheProvider);
    }

    public function testCreateCacheProviderForTransport()
    {
        $transportId = 1;
        $this->cacheProvider
            ->expects(static::once())
            ->method('setNamespace')
            ->with('oro_ups_time_in_transit_' . $transportId);

        $expectedTimeInTransitCacheProvider = new TimeInTransitCacheProvider($this->cacheProvider);

        $timeInTransitCacheProvider = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($transportId);

        static::assertEquals($expectedTimeInTransitCacheProvider, $timeInTransitCacheProvider);
    }

    public function testCreateCacheProviderForTransportReturnsSameInstance()
    {
        $transportId = 1;
        $this->cacheProvider
            ->expects(static::once())
            ->method('setNamespace')
            ->with('oro_ups_time_in_transit_' . $transportId);

        $timeInTransitCacheProvider1 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($transportId);

        $timeInTransitCacheProvider2 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($transportId);

        static::assertSame($timeInTransitCacheProvider2, $timeInTransitCacheProvider1);
    }
}
