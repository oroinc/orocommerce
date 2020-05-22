<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryCountsCacheTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var CategoryCountsCache */
    protected $cache;

    protected function setUp(): void
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);
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
            ->method('fetch')
            ->with($key . '|33|42')
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
            ->method('fetch')
            ->with($key . '|0|0')
            ->willReturn(false);

        $this->assertNull($this->cache->getCounts($key));
    }

    /**
     * @dataProvider cacheDataProvider
     *
     * @param string $key
     * @param int $userId
     * @param string $expectedKey
     */
    public function testGetCounts($key, $userId, $expectedKey)
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
            ->method('fetch')
            ->with($expectedKey)
            ->willReturn($data);

        $this->assertSame($data, $this->cache->getCounts($key));
    }

    /**
     * @dataProvider cacheDataProvider
     *
     * @param string $key
     * @param int $userId
     * @param string $expectedKey
     */
    public function testSetCounts($key, $userId, $expectedKey)
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
            ->method('save')
            ->with($expectedKey, $data, $lifeTime);

        $this->cache->setCounts($key, $data, $lifeTime);
    }

    /**
     * @return array
     */
    public function cacheDataProvider()
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
