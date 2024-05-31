<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Cache;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCache;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class FedexResponseCacheTest extends TestCase
{
    /**
     * @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheItem;

    /**
     * @var FedexResponseCache
     */
    private $fedexCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->fedexCache = new FedexResponseCache($this->cache);
    }

    public function testHas(): void
    {
        $key = $this->createCacheKey();

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);

        self::assertTrue($this->fedexCache->has($key));
    }

    public function testGetNoResponse(): void
    {
        $key = $this->createCacheKey();

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        self::assertNull($this->fedexCache->get($key));
    }

    public function testGet(): void
    {
        $key = $this->createCacheKey();
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($response);

        self::assertSame($response, $this->fedexCache->get($key));
    }

    public function testSetInvalidateAtIsSetInSettings(): void
    {
        $datetime = new \DateTime('now +1 day');
        $key = $this->createCacheKey($datetime);
        $response = $this->createMock(FedexRateServiceResponseInterface::class);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with($datetime->getTimestamp()-\date_timestamp_get(\date_create()))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($response);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        self::assertTrue($this->fedexCache->set($key, $response));
    }

    public function testSetInvalidateAtNotSetInSettings(): void
    {
        $key = $this->createCacheKey();
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(86400)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($response);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem)
            ->willReturn(true);

        self::assertTrue($this->fedexCache->set($key, $response));
    }

    public function testDelete(): void
    {
        $key = $this->createCacheKey();

        $this->cache
            ->expects(self::once())
            ->method('deleteItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn(true);

        self::assertTrue($this->fedexCache->delete($key));
    }

    public function testDeleteAll(): void
    {
        $this->cache
            ->expects(self::once())
            ->method('clear')
            ->willReturn(true);

        self::assertTrue($this->fedexCache->deleteAll());
    }

    private function createCacheKey(\DateTime $invalidateAt = null): FedexResponseCacheKey
    {
        return new FedexResponseCacheKey(
            new FedexRequest('test/uri'),
            (new FedexIntegrationSettings())->setInvalidateCacheAt($invalidateAt)
        );
    }

    private function normalizeCacheKey(FedexResponseCacheKey $key): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(
            $key->getCacheKey() . '_' .  $key->getSettings()->getId()
        );
    }
}
