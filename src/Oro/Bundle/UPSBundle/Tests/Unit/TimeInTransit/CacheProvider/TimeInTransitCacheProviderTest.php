<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\TimeInTransit\CacheProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProvider;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

class TimeInTransitCacheProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @internal
     */
    const CACHE_KEY = 'US:12345:US:12345:201801011200';

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
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheProvider;

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
    protected function setUp()
    {
        $this->address = new AddressStub();
        $this->pickupDate = \DateTime::createFromFormat('d.m.Y H:i', self::PICKUP_DATE);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->timeInTransitCacheProvider = new TimeInTransitCacheProvider($this->cacheProvider);
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
        $timeInTransitResult = $this->createMock(TimeInTransitResultInterface::class);

        $this->cacheProvider
            ->expects(static::once())
            ->method('save')
            ->with(self::CACHE_KEY, $timeInTransitResult, self::LIFETIME);

        $this->timeInTransitCacheProvider
            ->save($this->address, $this->address, $this->pickupDate, $timeInTransitResult, self::LIFETIME);
    }
}
