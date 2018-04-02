<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider\Factory;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
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
     * @var LifetimeProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lifetimeProvider;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->lifetimeProvider = $this->createMock(LifetimeProviderInterface::class);
        $this->timeInTransitCacheProviderFactory = new TimeInTransitCacheProviderFactory(
            $this->cacheProvider,
            $this->lifetimeProvider
        );
    }

    public function testCreateCacheProviderForTransport()
    {
        $settings = $this->createSettingsMock();

        $settings->method('getId')
            ->willReturn(1);

        $this->cacheProvider
            ->expects(static::once())
            ->method('setNamespace')
            ->with('oro_ups_time_in_transit_' . 1);

        $expectedTimeInTransitCacheProvider = new TimeInTransitCacheProvider(
            $settings,
            $this->cacheProvider,
            $this->lifetimeProvider
        );

        $timeInTransitCacheProvider = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        static::assertEquals($expectedTimeInTransitCacheProvider, $timeInTransitCacheProvider);
    }

    public function testCreateCacheProviderForTransportReturnsSameInstance()
    {
        $settings = $this->createSettingsMock();

        $settings->method('getId')
            ->willReturn(1);

        $this->cacheProvider
            ->expects(static::once())
            ->method('setNamespace')
            ->with('oro_ups_time_in_transit_' . 1);

        $timeInTransitCacheProvider1 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        $timeInTransitCacheProvider2 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        static::assertSame($timeInTransitCacheProvider2, $timeInTransitCacheProvider1);
    }

    /**
     * @return UPSSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSettingsMock()
    {
        return $this->createMock(UPSSettings::class);
    }
}
