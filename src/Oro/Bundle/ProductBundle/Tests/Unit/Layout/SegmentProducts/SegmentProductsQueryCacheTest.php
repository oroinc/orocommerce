<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\SegmentProducts;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\SegmentProducts\SegmentProductsQueryCache;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class SegmentProductsQueryCacheTest extends \PHPUnit\Framework\TestCase
{
    private const CACHE_LIFE_TIME = 1234;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $crypter;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var SegmentProductsQueryCache */
    private $segmentProductsQueryCache;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->expects(self::any())
            ->method('getConfiguration')
            ->willReturn(new Configuration());

        $this->segmentProductsQueryCache = new SegmentProductsQueryCache(
            $this->doctrine,
            $this->crypter,
            $this->cache,
            self::CACHE_LIFE_TIME
        );
    }

    private function getHash(string $dql, array $parameters, array $hints): string
    {
        return md5(serialize(['dql' => $dql, 'parameters' => $parameters, 'hints' => $hints]));
    }

    public function testGetQueryWhenQueryExistsInCache(): void
    {
        $cacheKey = 'test_cache_key';
        $cachedData = [
            'dql'        => 'DQL SELECT',
            'parameters' => ['param1' => 1],
            'hints'      => ['hint1' => 1],
            'hash'       => 'test_encrypted_hash'
        ];

        $query = new Query($this->em);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->with($cachedData['hash'])
            ->willReturn($this->getHash($cachedData['dql'], $cachedData['parameters'], $cachedData['hints']));
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $this->em->expects(self::once())
            ->method('createQuery')
            ->with($cachedData['dql'])
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryCache->getQuery($cacheKey));
        self::assertCount(1, $query->getParameters());
        self::assertSame($cachedData['parameters']['param1'], $query->getParameter('param1')->getValue());
        self::assertSame($cachedData['hints'], $query->getHints());
    }

    public function testGetQueryWhenQueryWithoutHintsExistsInCache(): void
    {
        $cacheKey = 'test_cache_key';
        $cachedData = [
            'dql'        => 'DQL SELECT',
            'parameters' => ['param1' => 1],
            'hints'      => [],
            'hash'       => 'test_encrypted_hash'
        ];

        $query = new Query($this->em);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->with($cachedData['hash'])
            ->willReturn($this->getHash($cachedData['dql'], $cachedData['parameters'], []));
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
        $this->em->expects(self::once())
            ->method('createQuery')
            ->with($cachedData['dql'])
            ->willReturn($query);

        self::assertSame($query, $this->segmentProductsQueryCache->getQuery($cacheKey));
        self::assertCount(1, $query->getParameters());
        self::assertSame($cachedData['parameters']['param1'], $query->getParameter('param1')->getValue());
        self::assertSame([], $query->getHints());
    }

    public function testGetQueryWhenQueryWithInvalidHashExistsInCache(): void
    {
        $cacheKey = 'test_cache_key';
        $cachedData = [
            'dql'        => 'DQL SELECT',
            'parameters' => ['param1' => 1],
            'hints'      => ['hint1' => 1],
            'hash'       => 'test_encrypted_hash'
        ];

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->crypter->expects(self::once())
            ->method('decryptData')
            ->with($cachedData['hash'])
            ->willReturn('invalid hash');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertNull($this->segmentProductsQueryCache->getQuery($cacheKey));
    }

    /**
     * @dataProvider invalidCachedQueryDataProvider
     */
    public function testGetQueryWhenQueryWithInvalidQueryDataExistsInCache(array $cachedData): void
    {
        $cacheKey = 'test_cache_key';

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($cachedData);
        $this->crypter->expects(self::never())
            ->method('decryptData');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertNull($this->segmentProductsQueryCache->getQuery($cacheKey));
    }

    public function invalidCachedQueryDataProvider(): array
    {
        return [
            'empty'        => [[]],
            'empty hash'   => [['dql' => 'DQL', 'parameters' => ['p1' => 1], 'hints' => ['h1' => 1], 'hash' => '']],
            'no hash'      => [['dql' => 'DQL', 'parameters' => ['p1' => 1], 'hints' => ['h1' => 1]]],
            'empty params' => [['dql' => 'DQL', 'parameters' => [], 'hints' => ['h1' => 1], 'hash' => 'h']],
            'no params'    => [['dql' => 'DQL', 'hints' => ['h1' => 1], 'hash' => 'h']],
            'empty dql'    => [['dql' => '', 'parameters' => ['p1' => 1], 'hints' => ['h1' => 1], 'hash' => '']],
            'no dql'       => [['parameters' => ['p1' => 1], 'hints' => ['h1' => 1], 'hash' => '']],
        ];
    }

    public function testGetQueryWhenQueryDoesNotExistsInCache(): void
    {
        $cacheKey = 'test_cache_key';

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->crypter->expects(self::never())
            ->method('decryptData');
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertNull($this->segmentProductsQueryCache->getQuery($cacheKey));
    }

    public function testSetQuery(): void
    {
        $cacheKey = 'test_cache_key';
        $dataToSaveInCache = [
            'dql'        => 'DQL SELECT',
            'parameters' => ['param1' => 1],
            'hints'      => ['hint1' => 1],
            'hash'       => 'test_encrypted_hash'
        ];

        $query = new Query($this->em);
        $query->setDQL($dataToSaveInCache['dql']);
        $query->setParameter('param1', $dataToSaveInCache['parameters']['param1']);
        $query->setHint('hint1', $dataToSaveInCache['hints']['hint1']);

        $this->crypter->expects(self::once())
            ->method('encryptData')
            ->with(
                $this->getHash(
                    $dataToSaveInCache['dql'],
                    $dataToSaveInCache['parameters'],
                    $dataToSaveInCache['hints']
                )
            )
            ->willReturn($dataToSaveInCache['hash']);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($cacheKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with(self::CACHE_LIFE_TIME)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($dataToSaveInCache);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $this->segmentProductsQueryCache->setQuery($cacheKey, $query);
    }
}
