<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider\Factory;

use Oro\Bundle\UPSBundle\Cache\Lifetime\LifetimeProviderInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactory;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class TimeInTransitCacheProviderFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var LifetimeProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $lifetimeProvider;

    /** @var TimeInTransitCacheProviderFactory */
    private $timeInTransitCacheProviderFactory;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(AbstractAdapter::class);
        $this->lifetimeProvider = $this->createMock(LifetimeProviderInterface::class);

        $this->timeInTransitCacheProviderFactory = new TimeInTransitCacheProviderFactory(
            $this->cacheProvider,
            $this->lifetimeProvider
        );
    }

    public function testCreateCacheProviderForTransport()
    {
        $settings = $this->createMock(UPSSettings::class);
        $settings->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $this->cacheProvider->expects(self::once())
            ->method('enableVersioning')
            ->with(true);

        $expectedTimeInTransitCacheProvider = new TimeInTransitCacheProvider(
            $settings,
            $this->cacheProvider,
            $this->lifetimeProvider
        );

        $timeInTransitCacheProvider = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        self::assertEquals($expectedTimeInTransitCacheProvider, $timeInTransitCacheProvider);
    }

    public function testCreateCacheProviderForTransportReturnsSameInstance()
    {
        $settings = $this->createMock(UPSSettings::class);
        $settings->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $this->cacheProvider->expects(self::once())
            ->method('enableVersioning')
            ->with(true);

        $timeInTransitCacheProvider1 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        $timeInTransitCacheProvider2 = $this->timeInTransitCacheProviderFactory
            ->createCacheProviderForTransport($settings);

        self::assertSame($timeInTransitCacheProvider2, $timeInTransitCacheProvider1);
    }
}
