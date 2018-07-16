<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Datagrid\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CatalogBundle\Datagrid\Cache\CategoryCountsCache;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;

class CategoryCountsCacheTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var CategoryCountsCache */
    protected $cache;

    protected function setUp()
    {
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);

        $this->cache = new CategoryCountsCache($this->cacheProvider, $this->tokenAccessor);
    }

    public function testGetCountsWithoutData()
    {
        $key = 'some_key';
        $userId = 42;

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with($key . '|42')
            ->willReturn(false);

        $this->cacheProvider->expects($this->never())
            ->method('fetch');

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

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

        $this->cacheProvider->expects($this->once())
            ->method('contains')
            ->with($expectedKey)
            ->willReturn(true);

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

        $this->tokenAccessor->expects($this->once())
            ->method('getUserId')
            ->willReturn($userId);

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
                'expectedKey' => '|0'
            ],
            'empty key' => [
                'key' => '',
                'userId' => 42,
                'expectedKey' => '|42'
            ],
            'empty userId' => [
                'gridName' => 'some_key',
                'userId' => null,
                'expectedKey' => 'some_key|0'
            ],
            'with all arguments' => [
                'gridName' => 'some_key',
                'userId' => 42,
                'expectedKey' => 'some_key|42'
            ],
        ];
    }
}
