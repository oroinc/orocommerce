<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentStatusRepository;
use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesCacheKeyProvider;
use Oro\Bundle\PaymentBundle\Provider\AvailablePaymentStatusesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

final class AvailablePaymentStatusesProviderTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private CacheItemPoolInterface&MockObject $cache;
    private AvailablePaymentStatusesCacheKeyProvider&MockObject $cacheKeyProvider;
    private AvailablePaymentStatusesProvider $provider;
    private array $defaultPaymentStatuses = ['pending', 'paid', 'failed'];

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheKeyProvider = $this->createMock(AvailablePaymentStatusesCacheKeyProvider::class);

        $this->provider = new AvailablePaymentStatusesProvider(
            $this->doctrine,
            $this->defaultPaymentStatuses,
            $this->cache,
            $this->cacheKeyProvider
        );
    }

    public function testGetAvailablePaymentStatusesWithNullEntityClass(): void
    {
        $result = $this->provider->getAvailablePaymentStatuses(null);

        self::assertEquals($this->defaultPaymentStatuses, $result);
    }

    public function testGetAvailablePaymentStatusesWithCacheHit(): void
    {
        $entityClass = Order::class;
        $cacheKey = 'cache_key_123';
        $cachedStatuses = ['pending', 'paid', 'custom_status'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem
            ->expects(self::once())
            ->method('get')
            ->willReturn($cachedStatuses);

        $this->cacheKeyProvider
            ->expects(self::once())
            ->method('getCacheKey')
            ->with($entityClass)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);

        // Repository should not be called when cache hits
        $this->doctrine
            ->expects(self::never())
            ->method('getRepository');

        $result = $this->provider->getAvailablePaymentStatuses($entityClass);

        self::assertEquals($cachedStatuses, $result);
    }

    public function testGetAvailablePaymentStatusesWithCacheMiss(): void
    {
        $entityClass = Order::class;
        $cacheKey = 'cache_key_123';
        $customStatuses = ['custom_status1', 'custom_status2'];
        $expectedResult = ['pending', 'paid', 'failed', 'custom_status1', 'custom_status2'];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->expects(self::once())
            ->method('set')
            ->with($expectedResult);

        $this->cacheKeyProvider
            ->expects(self::once())
            ->method('getCacheKey')
            ->with($entityClass)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);
        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $repository = $this->createMock(PaymentStatusRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAvailablePaymentStatusesForEntityClass')
            ->with($entityClass)
            ->willReturn($customStatuses);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $result = $this->provider->getAvailablePaymentStatuses($entityClass);

        self::assertEquals($expectedResult, $result);
    }

    public function testGetAvailablePaymentStatusesWithDuplicateStatuses(): void
    {
        $entityClass = Order::class;
        $cacheKey = 'cache_key_123';
        $customStatuses = ['pending', 'custom_status', 'paid']; // 'pending' and 'paid' are in default statuses
        $expectedResult = ['pending', 'paid', 'failed', 'custom_status']; // No duplicates

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->expects(self::once())
            ->method('set')
            ->with($expectedResult);

        $this->cacheKeyProvider
            ->expects(self::once())
            ->method('getCacheKey')
            ->with($entityClass)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);
        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $repository = $this->createMock(PaymentStatusRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAvailablePaymentStatusesForEntityClass')
            ->with($entityClass)
            ->willReturn($customStatuses);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $result = $this->provider->getAvailablePaymentStatuses($entityClass);

        self::assertEquals($expectedResult, $result);
        self::assertCount(4, $result); // Ensure no duplicates
    }

    public function testGetAvailablePaymentStatusesWithEmptyCustomStatuses(): void
    {
        $entityClass = Order::class;
        $cacheKey = 'cache_key_123';
        $customStatuses = [];
        $expectedResult = $this->defaultPaymentStatuses;

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem
            ->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem
            ->expects(self::once())
            ->method('set')
            ->with($expectedResult);

        $this->cacheKeyProvider
            ->expects(self::once())
            ->method('getCacheKey')
            ->with($entityClass)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($cacheItem);
        $this->cache
            ->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $repository = $this->createMock(PaymentStatusRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAvailablePaymentStatusesForEntityClass')
            ->with($entityClass)
            ->willReturn($customStatuses);

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->with(PaymentStatus::class)
            ->willReturn($repository);

        $result = $this->provider->getAvailablePaymentStatuses($entityClass);

        self::assertEquals($expectedResult, $result);
    }

    public function testGetAvailablePaymentStatusesWithNonExistentClass(): void
    {
        $nonExistentClass = 'NonExistent\Entity\Class';

        $result = $this->provider->getAvailablePaymentStatuses($nonExistentClass);

        self::assertEquals($this->defaultPaymentStatuses, $result);
    }

    public function testGetAvailablePaymentStatusesWithEmptyEntityClass(): void
    {
        $result = $this->provider->getAvailablePaymentStatuses('');

        self::assertEquals($this->defaultPaymentStatuses, $result);
    }
}
