<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\AddressValidation\Cache;

use Oro\Bundle\AddressValidationBundle\Cache\AddressValidationCacheKey;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\AddressValidationBundle\Client\Response\AddressValidationResponse;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Cache\FedexAddressValidationResponseCache;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class FedexAddressValidationResponseCacheTest extends TestCase
{
    private CacheItemPoolInterface|MockObject $cache;
    private CacheItemInterface|MockObject $cacheItem;

    private FedexAddressValidationResponseCache $fedexCache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->fedexCache = new FedexAddressValidationResponseCache($this->cache);
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
        $response = $this->createMock(AddressValidationResponse::class);

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
        $response = $this->createMock(AddressValidationResponse::class);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($this->normalizeCacheKey($key))
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with($datetime->getTimestamp() - \date_timestamp_get(\date_create()))
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
        $response = $this->createMock(AddressValidationResponse::class);

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

    private function createCacheKey(?\DateTime $invalidateAt = null): AddressValidationCacheKey
    {
        return new AddressValidationCacheKey(
            new AddressValidationRequest('test/uri', ['test']),
            (new FedexIntegrationSettings())->setInvalidateCacheAt($invalidateAt)
        );
    }

    private function normalizeCacheKey(AddressValidationCacheKey $key): string
    {
        return UniversalCacheKeyGenerator::normalizeCacheKey(sprintf(
            '%s_%s_%s_%s',
            $key->getCacheKey(),
            $key->getTransport()->getId(),
            $key->getTransport()->getClientId(),
            $key->getTransport()->getClientSecret()
        ));
    }
}
