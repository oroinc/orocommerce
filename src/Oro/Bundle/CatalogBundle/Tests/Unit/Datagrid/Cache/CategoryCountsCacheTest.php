<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Cache;

use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class CategoryCountsCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CategoryCountsCache */
    private $cache;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->cache = new CategoryCountsCache($this->cacheProvider, $this->tokenAccessor, $this->websiteManager);
    }

    public function testGetCountsWithoutData()
    {
        $key = 'some_key';
        $userId = 42;
        $website = $this->getEntity(Website::class, ['id' => 33]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with($key . '|33|42')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        $this->assertNull($this->cache->getCounts($key));
    }

    public function testGetCountsWithoutDataWithoutWebsiteAndWithoutCustomerUser()
    {
        $key = 'some_key';
        $userId = null;

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with($key . '|0|0')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);

        $this->assertNull($this->cache->getCounts($key));
    }

    /**
     * @dataProvider cacheDataProvider
     */
    public function testGetCounts(string $key, ?int $userId, string $expectedKey)
    {
        $data = ['cache' => 'data'];
        $website = $this->getEntity(Website::class, ['id' => 33]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with($expectedKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $this->cacheItem->expects(self::once())
            ->method('get')
            ->willReturn($data);


        $this->assertSame($data, $this->cache->getCounts($key));
    }

    /**
     * @dataProvider cacheDataProvider
     */
    public function testSetCounts(string $key, ?int $userId, string $expectedKey)
    {
        $data = ['cache' => 'data'];
        $lifeTime = 100500;
        $website = $this->getEntity(Website::class, ['id' => 33]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cacheProvider->expects($this->once())
            ->method('getItem')
            ->with($expectedKey)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($data)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('expiresAfter')
            ->with($lifeTime)
            ->willReturn($this->cacheItem);
        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->cache->setCounts($key, $data, $lifeTime);
    }

    public function cacheDataProvider(): array
    {
        return [
            'empty key and userId' => [
                'key' => '',
                'userId' => null,
                'expectedKey' => '|33|0'
            ],
            'empty key' => [
                'key' => '',
                'userId' => 42,
                'expectedKey' => '|33|42'
            ],
            'empty userId' => [
                'gridName' => 'some_key',
                'userId' => null,
                'expectedKey' => 'some_key|33|0'
            ],
            'with all arguments' => [
                'gridName' => 'some_key',
                'userId' => 42,
                'expectedKey' => 'some_key|33|42'
            ],
        ];
    }
}
